<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Services;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature;
use Illuminate\Contracts\Auth\Authenticatable as User;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Log\LoggerInterface;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUsed;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AssertionFailed;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AttestationFailed;
use Rawilk\ProfileFilament\Exceptions\Webauthn\ResponseMismatch;
use Webauthn\AttestationStatement;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\Exception\WebauthnException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class Webauthn
{
    protected Manager $algorithmManager;

    protected AttestationStatementSupportManager $attestationStatementSupportManager;

    protected AttestationStatement\AttestationObjectLoader $attestationObjectLoader;

    protected PublicKeyCredentialLoader $publicKeyCredentialLoader;

    protected ExtensionOutputCheckerHandler $extensionOutputCheckerHandler;

    protected AuthenticatorAttestationResponseValidator $authenticatorAttestationResponseValidator;

    protected AuthenticatorAssertionResponseValidator $authenticatorAssertionResponseValidator;

    /**
     * A callback responsible for generating a challenge with. This is mostly useful
     * in testing scenarios.
     *
     * @var null|callable
     */
    protected static $generateChallengeCallback = null;

    public function __construct(
        protected string $model,
        protected LoggerInterface $logger,
    ) {
        $this->initialize();
    }

    /**
     * Define a callback to generate the challenges with.
     */
    public static function generateChallengeWith(?callable $callback): void
    {
        static::$generateChallengeCallback = $callback;
    }

    public function attestationObjectFor(string $username, string|int|null $userId = null): PublicKeyCredentialCreationOptions
    {
        // RP Entity i.e. the application
        $rpEntity = PublicKeyCredentialRpEntity::create(
            name: config('profile-filament.webauthn.relying_party.name'),
            id: parse_url(config('profile-filament.webauthn.relying_party.id'), PHP_URL_HOST),
            icon: config('profile-filament.webauthn.relying_party.icon'),
        );

        // User Entity
        $userEntity = PublicKeyCredentialUserEntity::create(
            name: $username,
            id: (string) ($userId ?? $username),
            displayName: $username,
        );

        return PublicKeyCredentialCreationOptions::create(
            rp: $rpEntity,
            user: $userEntity,
            challenge: $this->generateChallenge(),
            pubKeyCredParams: $this->getPubKeyCredParams(),
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                authenticatorAttachment: config('profile-filament.webauthn.authenticator_attachment', AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE),
                userVerification: config('profile-filament.webauthn.user_verification', AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED),
                residentKey: config('profile-filament.webauthn.resident_key', AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED),
            ),
            attestation: config('profile-filament.webauthn.attestation_conveyance', PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE),
            excludeCredentials: $this->getPublicKeyCredentialDescriptorsFor($userId),
            timeout: config('profile-filament.webauthn.timeout', 60_000),
        );
    }

    public function passkeyAttestationObjectFor(string $username, string|int|null $userId = null, array $excludeCredentials = []): PublicKeyCredentialCreationOptions
    {
        // RP Entity i.e. the application
        $rpEntity = PublicKeyCredentialRpEntity::create(
            name: config('profile-filament.webauthn.relying_party.name'),
            id: parse_url(config('profile-filament.webauthn.relying_party.id'), PHP_URL_HOST),
            icon: config('profile-filament.webauthn.relying_party.icon'),
        );

        // User Entity
        $userEntity = PublicKeyCredentialUserEntity::create(
            name: $username,
            id: (string) ($userId ?? $username),
            displayName: $username,
        );

        return PublicKeyCredentialCreationOptions::create(
            rp: $rpEntity,
            user: $userEntity,
            challenge: $this->generateChallenge(),
            pubKeyCredParams: $this->getPubKeyCredParams(),
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                // Resident key and user verification are required for passkeys
                authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            excludeCredentials: $this->getPublicKeyCredentialDescriptorsFor($userId, $excludeCredentials),
            timeout: config('profile-filament.webauthn.passkey_timeout', 300_000),
        );
    }

    public function verifyAttestation(array $attestationResponse, PublicKeyCredentialCreationOptions $storedPublicKey): PublicKeyCredentialSource
    {
        $publicKeyCredential = $this->publicKeyCredentialLoader->loadArray($attestationResponse);

        throw_unless(
            $publicKeyCredential->response instanceof AuthenticatorAttestationResponse,
            ResponseMismatch::attestation(),
        );

        try {
            return $this->authenticatorAttestationResponseValidator->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: $storedPublicKey,
                request: parse_url(config('profile-filament.webauthn.relying_party.id'), PHP_URL_HOST),
            );
        } catch (WebauthnException $e) {
            throw AttestationFailed::fromWebauthnException($e);
        }
    }

    public function assertionObjectFor(string|int|null $userId = null): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: $this->generateChallenge(),
            rpId: parse_url(config('profile-filament.webauthn.relying_party.id'), PHP_URL_HOST),
            allowCredentials: $this->getPublicKeyCredentialDescriptorsFor($userId),
            userVerification: config('profile-filament.webauthn.user_verification', AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED),
            timeout: config('profile-filament.webauthn.timeout', 60_000),
        );
    }

    public function passkeyAssertionObject(): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: $this->generateChallenge(),
            rpId: parse_url(config('profile-filament.webauthn.relying_party.id'), PHP_URL_HOST),
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: config('profile-filament.webauthn.passkey_timeout', 300_000)
        );
    }

    public function verifyAssertion(
        ?User $user,
        array $assertionResponse,
        PublicKeyCredentialRequestOptions $storedPublicKey,
        bool $requiresPasskey = false,
    ): array {
        $publicKeyCredential = $this->publicKeyCredentialLoader->loadArray($assertionResponse);

        throw_unless(
            $publicKeyCredential->response instanceof AuthenticatorAssertionResponse,
            ResponseMismatch::assertion(),
        );

        $authenticator = $this->model::query()
            ->when($user, fn ($query) => $query->where('user_id', $user->getAuthIdentifier()))
            ->byCredentialId($publicKeyCredential->rawId)
            ->with('user')
            ->first(['id', 'user_id', 'is_passkey', 'public_key']);

        throw_unless(
            filled($authenticator) && filled($authenticator->user),
            AssertionFailed::keyNotFound(Base64UrlSafe::encodeUnpadded($publicKeyCredential->rawId)),
        );

        throw_if(
            $requiresPasskey && ! $authenticator->is_passkey,
            AssertionFailed::passkeyRequired(),
        );

        try {
            $publicKeyCredentialSource = $this->authenticatorAssertionResponseValidator->check(
                credentialId: $authenticator->public_key_credential_source,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: $storedPublicKey,
                request: parse_url(config('profile-filament.webauthn.relying_party.id'), PHP_URL_HOST),
                userHandle: $publicKeyCredential->response->userHandle,
            );
        } catch (WebauthnException $e) {
            throw AssertionFailed::fromWebauthnException($e);
        }

        // If we've gotten this far, the response is valid.
        // We're updating the public_key on the credential in case the counter increased.
        $authenticator->fill([
            'public_key' => $publicKeyCredentialSource->jsonSerialize(),
            'last_used_at' => now(),
        ])->save();

        WebauthnKeyUsed::dispatch($authenticator->user, $authenticator);

        return [
            'publicKeyCredentialSource' => $publicKeyCredentialSource,
            'authenticator' => $authenticator,
        ];
    }

    /**
     * Get a list of keys associated with a given user for the registration/authentication process.
     *
     * @return array<int, PublicKeyCredentialDescriptor>
     */
    public function getPublicKeyCredentialDescriptorsFor(string|int $userId, array $exclude = []): array
    {
        return $this->model::query()
            ->where('user_id', $userId)
            ->when(filled($exclude), fn ($query) => $query->whereNotIn('id', $exclude))
            ->pluck('public_key')
            ->map(
                fn (array $publicKey): PublicKeyCredentialSource => PublicKeyCredentialSource::createFromArray($publicKey)
            )
            ->map(
                fn (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor => $credential->getPublicKeyCredentialDescriptor()
            )
            ->toArray();
    }

    /**
     * @return array<int, \Webauthn\PublicKeyCredentialParameters>
     */
    protected function getPubKeyCredParams(): array
    {
        return collect($this->algorithmManager->list())
            ->map(function (int $algorithm): PublicKeyCredentialParameters {
                return PublicKeyCredentialParameters::create(
                    type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                    alg: $algorithm,
                );
            })
            ->toArray();
    }

    protected function generateChallenge(): string
    {
        if (is_callable(static::$generateChallengeCallback)) {
            return call_user_func(static::$generateChallengeCallback);
        }

        return random_bytes(32);
    }

    /**
     * @see https://webauthn-doc.spomky-labs.com/pure-php/the-hard-way
     */
    protected function initialize(): void
    {
        $this->setAlgorithmManager();
        $this->setAttestationStatementSupportManagers();
        $this->setAttestationObjectLoader();
        $this->setPublicKeyCredentialLoader();
        $this->setExtensionOutputCheckerHandler();

        $this->setAuthenticatorAttestationResponseValidator();
        $this->setAuthenticatorAssertionResponseValidator();
    }

    /**
     * The Webauthn data verification is based on cryptographic signatures, and thus you need
     * to provide cryptographic algorithms to perform those checks.
     */
    protected function setAlgorithmManager(): void
    {
        $this->algorithmManager = Manager::create()->add(
            Signature\ECDSA\ES256::create(),
            Signature\ECDSA\ES256K::create(),
            Signature\ECDSA\ES384::create(),
            Signature\ECDSA\ES512::create(),
            Signature\RSA\RS256::create(),
            Signature\RSA\RS384::create(),
            Signature\RSA\RS512::create(),
            Signature\RSA\PS256::create(),
            Signature\RSA\PS384::create(),
            Signature\RSA\PS512::create(),
            Signature\EdDSA\Ed256::create(),
            Signature\EdDSA\Ed512::create(),
        );
    }

    protected function setAttestationStatementSupportManagers(): void
    {
        $this->attestationStatementSupportManager = AttestationStatementSupportManager::create();

        $this->attestationStatementSupportManager->add(AttestationStatement\NoneAttestationStatementSupport::create());
        $this->attestationStatementSupportManager->add(AttestationStatement\FidoU2FAttestationStatementSupport::create());
        $this->attestationStatementSupportManager->add(AttestationStatement\AndroidKeyAttestationStatementSupport::create());
        $this->attestationStatementSupportManager->add(
            AttestationStatement\PackedAttestationStatementSupport::create($this->algorithmManager)
        );
        $this->attestationStatementSupportManager->add(AttestationStatement\AppleAttestationStatementSupport::create());
    }

    /**
     * This object will load the Attestation statements received from devices. It requires the
     * Attestation Statement Support Manager object.
     */
    protected function setAttestationObjectLoader(): void
    {
        $this->attestationObjectLoader = AttestationStatement\AttestationObjectLoader::create(
            $this->attestationStatementSupportManager
        );

        $this->attestationObjectLoader->setLogger($this->logger);
    }

    /**
     * This object will load the Public Key used from the Attestation Object.
     */
    protected function setPublicKeyCredentialLoader(): void
    {
        $this->publicKeyCredentialLoader = PublicKeyCredentialLoader::create(
            $this->attestationObjectLoader
        );

        $this->publicKeyCredentialLoader->setLogger($this->logger);
    }

    /**
     * If extensions are used, the value may need to be checked by this object.
     * We are not using extensions at this time, so we'll just use a base object.
     */
    protected function setExtensionOutputCheckerHandler(): void
    {
        $this->extensionOutputCheckerHandler = ExtensionOutputCheckerHandler::create();
    }

    /**
     * This object is responsible for receiving attestation responses (authenticator registration).
     */
    protected function setAuthenticatorAttestationResponseValidator(): void
    {
        $this->authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create(
            attestationStatementSupportManager: $this->attestationStatementSupportManager,
            publicKeyCredentialSourceRepository: null,
            tokenBindingHandler: null,
            extensionOutputCheckerHandler: $this->extensionOutputCheckerHandler,
        );

        $this->authenticatorAttestationResponseValidator->setLogger($this->logger);
    }

    /**
     * This object is responsible for receiving assertion responses (user authentication).
     */
    protected function setAuthenticatorAssertionResponseValidator(): void
    {
        $this->authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            publicKeyCredentialSourceRepository: null,
            tokenBindingHandler: null,
            extensionOutputCheckerHandler: $this->extensionOutputCheckerHandler,
            algorithmManager: $this->algorithmManager,
        );

        $this->authenticatorAssertionResponseValidator->setLogger($this->logger);
    }
}
