<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions;

use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts\HasWebauthn;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Exceptions\InvalidSecurityKey;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Exceptions\InvalidSecurityKeyOptions;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Support\CredentialRecordConverter;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Support\Serializer;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyRegistered;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Support\Config;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

class StoreSecurityKeyAction
{
    public function __invoke(
        HasWebauthn $user,
        string $securityKeyJson,
        string $securityKeyOptionsJson,
        string $hostName,
        array $additionalData = [],
    ): WebauthnKey {
        $credentialRecord = $this->determinePublicKeyCredentialSource(
            $securityKeyJson,
            $securityKeyOptionsJson,
            $hostName,
        );

        $attachmentType = $this->getAttachmentType($securityKeyJson);

        // The model instance will set and encode the credential id when 'data' is set.
        $securityKey = $user->securityKeys()->create([
            ...$additionalData,
            'data' => $credentialRecord,
            'attachment_type' => $attachmentType,

            // For now, we'll consider all platform keys to be passkeys (i.e., password manager, Apple Face ID, Windows Hello)
            'is_passkey' => $attachmentType === AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
        ]);

        app(MarkTwoFactorEnabledAction::class)($user);

        WebauthnKeyRegistered::dispatch($securityKey, $user);

        return $securityKey;
    }

    protected function determinePublicKeyCredentialSource(
        string $securityKeyJson,
        string $securityKeyOptionsJson,
        string $hostName,
    ): PublicKeyCredentialSource {
        $securityKeyOptions = $this->getSecurityKeyOptions($securityKeyOptionsJson);

        $publicKeyCredential = $this->getSecurityKey($securityKeyJson);

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw InvalidSecurityKey::invalidPublicKeyCredential();
        }

        $configureCeremonyStepManagerFactory = Config::getWebauthnAction(
            'configure_ceremony_step_manager_factory',
            ConfigureCeremonyStepManagerFactoryAction::class,
        );
        $csmFactory = $configureCeremonyStepManagerFactory();
        $creationCsm = $csmFactory->creationCeremony();

        try {
            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create($creationCsm)->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: $securityKeyOptions,
                host: $hostName,
            );
        } catch (Throwable $exception) {
            throw InvalidSecurityKey::invalidAuthenticatorAttestationResponse($exception);
        }

        return CredentialRecordConverter::toPublicKeyCredentialSource($publicKeyCredentialSource);
    }

    protected function getSecurityKeyOptions(string $securityKeyOptionsJson): PublicKeyCredentialCreationOptions
    {
        if (! json_validate($securityKeyOptionsJson)) {
            throw InvalidSecurityKeyOptions::invalidJson();
        }

        /** @var PublicKeyCredentialCreationOptions $securityKeyOptions */
        $securityKeyOptions = Serializer::make()->fromJson(
            $securityKeyOptionsJson,
            PublicKeyCredentialCreationOptions::class,
        );

        return $securityKeyOptions;
    }

    protected function getSecurityKey(string $securityKeyJson): PublicKeyCredential
    {
        if (! json_validate($securityKeyJson)) {
            throw InvalidSecurityKey::invalidJson();
        }

        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = Serializer::make()->fromJson(
            $securityKeyJson,
            PublicKeyCredential::class,
        );

        return $publicKeyCredential;
    }

    protected function getAttachmentType(string $securityKeyJson): ?string
    {
        $data = json_decode($securityKeyJson, true);

        return data_get($data, 'authenticatorAttachment', AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM);
    }
}
