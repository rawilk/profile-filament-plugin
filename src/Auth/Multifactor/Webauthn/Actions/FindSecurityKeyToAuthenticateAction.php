<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Support\CredentialRecordConverter;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Support\Serializer;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Support\Config;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

class FindSecurityKeyToAuthenticateAction
{
    public function __invoke(
        string $publicKeyCredentialJson,
        string $securityKeyOptionsJson,

        // If true, only passkeys can be used for the authentication. This should be true
        // when using userless, passkey login.
        bool $requiresPasskey = false,

        // Providing a user is mostly useful to ensure cross-platform keys are being used to authenticate
        // the correct user account. It should be extremely rare for two users to have the same
        // key, but not impossible. Do not provide a user for passkey logins.
        ?Authenticatable $userBeingAuthenticated = null,
    ): ?WebauthnKey {
        $publicKeyCredential = $this->determinePublicKeyCredential($publicKeyCredentialJson);

        if (! $publicKeyCredential) {
            return null;
        }

        $securityKey = $this->findSecurityKey($publicKeyCredential, $requiresPasskey, $userBeingAuthenticated);

        if (! $securityKey) {
            return null;
        }

        /** @var PublicKeyCredentialRequestOptions $securityKeyOptions */
        $securityKeyOptions = Serializer::make()->fromJson(
            $securityKeyOptionsJson,
            PublicKeyCredentialRequestOptions::class,
        );

        $publicKeyCredentialSource = $this->determinePublicKeyCredentialSource(
            $publicKeyCredential,
            $securityKeyOptions,
            $securityKey,
        );

        if (! $publicKeyCredentialSource) {
            return null;
        }

        $this->updateSecurityKey($securityKey, $publicKeyCredentialSource);

        return $securityKey;
    }

    public function determinePublicKeyCredential(string $publicKeyCredentialJson): ?PublicKeyCredential
    {
        $publicKeyCredential = Serializer::make()->fromJson(
            $publicKeyCredentialJson,
            PublicKeyCredential::class,
        );

        if (! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return null;
        }

        return $publicKeyCredential;
    }

    protected function findSecurityKey(
        PublicKeyCredential $publicKeyCredential,
        bool $requiresPasskey,
        ?Authenticatable $user,
    ): ?WebauthnKey {
        /** @var class-string<WebauthnKey> $model */
        $model = Config::getModel('webauthn_key');

        return $model::query()
            ->byCredentialId($publicKeyCredential->rawId)
            ->when(
                $requiresPasskey,
                fn (Builder $query) => $query->passkey()
            )
            ->when(
                filled($user),
                fn (Builder $query) => $query->ownedBy($user)
            )
            ->first();
    }

    protected function determinePublicKeyCredentialSource(
        PublicKeyCredential $publicKeyCredential,
        PublicKeyCredentialRequestOptions $securityKeyOptions,
        WebauthnKey $securityKey,
    ): ?PublicKeyCredentialSource {
        $configureCeremonyStepManagerFactoryAction = Config::getWebauthnAction(
            'configure_ceremony_step_manager_factory',
            ConfigureCeremonyStepManagerFactoryAction::class,
        );

        $csmFactory = $configureCeremonyStepManagerFactoryAction();
        $requestCsm = $csmFactory->requestCeremony();

        try {
            $validator = AuthenticatorAssertionResponseValidator::create($requestCsm);

            $publicKeyCredentialSource = $validator->check(
                $securityKey->data,
                $publicKeyCredential->response,
                $securityKeyOptions,
                Config::getRelyingPartyId(),
                null,
            );
        } catch (Throwable) {
            return null;
        }

        return CredentialRecordConverter::toPublicKeyCredentialSource($publicKeyCredentialSource);
    }

    protected function updateSecurityKey(
        WebauthnKey $securityKey,
        PublicKeyCredentialSource $publicKeyCredentialSource,
    ): void {
        $securityKey->update([
            'data' => $publicKeyCredentialSource,
            'last_used_at' => now(),
        ]);
    }
}
