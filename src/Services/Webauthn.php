<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Services;

use const PHP_URL_HOST;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Str;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Log\LoggerInterface;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUsed;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AssertionFailed;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AttestationFailed;
use Rawilk\ProfileFilament\Exceptions\Webauthn\ResponseMismatch;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\Exception\WebauthnException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class Webauthn
{
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
    }

    /**
     * Define a callback to generate the challenges with.
     */
    public static function generateChallengeWith(?callable $callback): void
    {
        static::$generateChallengeCallback = $callback;
    }

    public function attestationObjectFor(User $user): PublicKeyCredentialCreationOptions
    {
        return PublicKeyCredentialCreationOptions::create(
            rp: $this->getRelyingParty(),
            user: $this->getUserEntity($user),
            challenge: $this->generateChallenge(),
            pubKeyCredParams: $this->getPubKeyCredParams(),
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                authenticatorAttachment: config('profile-filament.webauthn.authenticator_attachment', AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE),
                userVerification: config('profile-filament.webauthn.user_verification', AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED),
                residentKey: config('profile-filament.webauthn.resident_key', AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED),
            ),
            attestation: config('profile-filament.webauthn.attestation_conveyance', PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE),
            excludeCredentials: $this->getPublicKeyCredentialDescriptorsFor($user),
            timeout: config('profile-filament.webauthn.timeout', 60_000),
        );
    }

    public function passkeyAttestationObjectFor(?User $user = null, array $excludeCredentials = []): PublicKeyCredentialCreationOptions
    {
        return PublicKeyCredentialCreationOptions::create(
            rp: $this->getRelyingParty(),
            user: $this->getUserEntity($user),
            challenge: $this->generateChallenge(),
            pubKeyCredParams: $this->getPubKeyCredParams(),
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                // Resident key and user verification are required for passkeys
                authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            excludeCredentials: $this->getPublicKeyCredentialDescriptorsFor($user, $excludeCredentials),
            timeout: config('profile-filament.webauthn.passkey_timeout', 300_000),
        );
    }

    public function verifyAttestation(array $attestationResponse, PublicKeyCredentialCreationOptions $storedPublicKey): PublicKeyCredentialSource
    {
        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = $this->attestationSerializer()->deserialize(
            json_encode($attestationResponse),
            PublicKeyCredential::class,
            'json',
        );

        throw_unless(
            $publicKeyCredential->response instanceof AuthenticatorAttestationResponse,
            ResponseMismatch::attestation(),
        );

        try {
            return $this->attestationResponseValidator()->check(
                $publicKeyCredential->response,
                $storedPublicKey,
                host: parse_url(config('profile-filament.webauthn.relying_party.id'), PHP_URL_HOST),
            );
        } catch (WebauthnException $e) {
            throw AttestationFailed::fromWebauthnException($e);
        }
    }

    /**
     * @see https://webauthn-doc.spomky-labs.com/pure-php/authenticate-your-users
     */
    public function assertionObjectFor(User $user): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: $this->generateChallenge(),
            rpId: parse_url(config('profile-filament.webauthn.relying_party.id'), PHP_URL_HOST),
            allowCredentials: $this->getPublicKeyCredentialDescriptorsFor($user),
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

    /**
     * @see https://webauthn-doc.spomky-labs.com/pure-php/authenticate-your-users
     */
    public function verifyAssertion(
        ?User $user,
        array $assertionResponse,
        PublicKeyCredentialRequestOptions $storedPublicKey,
        bool $requiresPasskey = false,
    ): array {
        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = $this->assertionSerializer()->deserialize(
            json_encode($assertionResponse),
            PublicKeyCredential::class,
            'json',
        );

        throw_unless(
            $publicKeyCredential->response instanceof AuthenticatorAssertionResponse,
            ResponseMismatch::assertion(),
        );

        $authenticator = $this->model::query()
            ->when($user, fn ($query) => $query->where('user_id', $user->getAuthIdentifier()))
            ->byCredentialId($publicKeyCredential->rawId)
            ->with('user')
            ->first(['id', 'user_id', 'credential_id', 'is_passkey', 'public_key']);

        throw_unless(
            filled($authenticator) && filled($authenticator->user),
            AssertionFailed::keyNotFound(Base64UrlSafe::encodeUnpadded($publicKeyCredential->rawId)),
        );

        throw_if(
            $requiresPasskey && ! $authenticator->is_passkey,
            AssertionFailed::passkeyRequired(),
        );

        try {
            $publicKeyCredentialSource = $this->assertionResponseValidator()->check(
                publicKeyCredentialSource: $authenticator->public_key_credential_source,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: $storedPublicKey,
                host: parse_url(config('profile-filament.webauthn.relying_party.id'), PHP_URL_HOST),
                userHandle: $user ? $publicKeyCredential->response->userHandle : null,
            );
        } catch (WebauthnException $e) {
            throw AssertionFailed::fromWebauthnException($e);
        }

        /**
         * If we've gotten this far, the response is valid. We're updating the public_key
         * on the credential in case the counter increased.
         *
         * If we don't set the publicKeyCredentialId to its raw value here, future
         * checks with this key will fail. This is probably an indicator of a
         * bug somewhere in this implementation...
         */
        $publicKeyCredentialSource->publicKeyCredentialId = $publicKeyCredential->rawId;

        $authenticator->fill([
            'public_key' => $this->serializePublicKeyCredentialSource($publicKeyCredentialSource),
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
    public function getPublicKeyCredentialDescriptorsFor(User $user, array $exclude = []): array
    {
        return $user
            ->webauthnKeys()
            ->when(filled($exclude), fn ($query) => $query->whereKeyNot($exclude))
            ->pluck('credential_id')
            ->map(fn (string $credentialId) => PublicKeyCredentialDescriptor::create('public-key', $credentialId))
            ->toArray();
    }

    public function serializePublicKeyCredentialSource(PublicKeyCredentialSource $source): string
    {
        return $this->attestationSerializer()->serialize(
            $source,
            'json',
        );
    }

    public function unserializeKeyData(string $json): PublicKeyCredentialSource
    {
        $source = $this->attestationSerializer()->deserialize(
            $json,
            PublicKeyCredentialSource::class,
            'json',
        );

        return tap($source, function (PublicKeyCredentialSource $source) use ($json) {
            $parsedJson = json_decode($json, true);

            /**
             * The serializer does some kind of encoding to the credential id, so we need
             * set it to the un-encoded value, otherwise the credential id checks
             * during the ceremony will fail...
             */
            $source->publicKeyCredentialId = data_get($parsedJson, 'publicKeyCredentialId');
        });
    }

    public function serializePublicKeyOptionsForRequest(
        PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options,
    ): array {
        $data = [
            ...(array) $options,

            /**
             * We need to do this otherwise the challenge check will fail
             * in the next step of the process...
             *
             * @see https://github.com/web-auth/webauthn-framework/blob/4.8.x/src/webauthn/src/PublicKeyCredentialCreationOptions.php#L314
             */
            'challenge' => Base64UrlSafe::encodeUnpadded($options->challenge),
        ];

        if ($options instanceof PublicKeyCredentialCreationOptions) {
            $data['user'] = (array) $options->user;

            data_set(
                $data,
                'user.id',
                Base64UrlSafe::encodeUnpadded(data_get($data, 'user.id')),
            );
        }

        return $data;
    }

    /**
     * @return array<int, \Webauthn\PublicKeyCredentialParameters>
     */
    protected function getPubKeyCredParams(): array
    {
        return collect($this->algorithmManager()->list())
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

        return Str::random(32);
    }

    /**
     * The Webauthn data verification is based on cryptographic signatures, and thus you need
     * to provide cryptographic algorithms to perform those checks.
     *
     * @see https://webauthn-doc.spomky-labs.com/pure-php/advanced-behaviours/authenticator-algorithms
     */
    protected function algorithmManager(): Manager
    {
        return once(function (): Manager {
            return Manager::create()->add(
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
        });
    }

    /**
     * @see https://webauthn-doc.spomky-labs.com/pure-php/input-loading
     */
    protected function attestationSupportManager(): AttestationStatementSupportManager
    {
        return once(function (): AttestationStatementSupportManager {
            $manager = AttestationStatementSupportManager::create();

            $manager->add(AttestationStatement\NoneAttestationStatementSupport::create());
            $manager->add(AttestationStatement\FidoU2FAttestationStatementSupport::create());
            $manager->add(AttestationStatement\AndroidKeyAttestationStatementSupport::create());
            $manager->add(
                AttestationStatement\PackedAttestationStatementSupport::create($this->algorithmManager())
            );

            return $manager;
        });
    }

    /**
     * @see https://webauthn-doc.spomky-labs.com/pure-php/input-loading
     */
    protected function attestationSerializer(): SerializerInterface
    {
        return once(function (): SerializerInterface {
            return (new WebauthnSerializerFactory($this->attestationSupportManager()))->create();
        });
    }

    /**
     * This is exactly the same as parsing an attestation response, however I'm aliasing
     * it to this for readability.
     *
     * @see https://webauthn-doc.spomky-labs.com/pure-php/authenticate-your-users#data-loading
     */
    protected function assertionSerializer(): SerializerInterface
    {
        return $this->attestationSerializer();
    }

    /**
     * This object is responsible for receiving attestation responses (authenticator registration).
     *
     * @see https://webauthn-doc.spomky-labs.com/pure-php/input-validation
     */
    protected function attestationResponseValidator(): AuthenticatorAttestationResponseValidator
    {
        return once(function () {
            $factory = new CeremonyStepManagerFactory;
            $factory->setExtensionOutputCheckerHandler($this->extensionChecker());
            $factory->setAlgorithmManager($this->algorithmManager());

            $csm = $factory->creationCeremony();

            $validator = new AuthenticatorAttestationResponseValidator($csm);

            $validator->setLogger($this->logger);

            return $validator;
        });
    }

    /**
     * This object is responsible for validating assertion (login) responses.
     *
     * @see https://webauthn-doc.spomky-labs.com/pure-php/input-validation
     */
    protected function assertionResponseValidator(): AuthenticatorAssertionResponseValidator
    {
        return once(function () {
            $factory = new CeremonyStepManagerFactory;
            $factory->setExtensionOutputCheckerHandler($this->extensionChecker());
            $factory->setAlgorithmManager($this->algorithmManager());

            $csm = $factory->requestCeremony();

            $validator = new AuthenticatorAssertionResponseValidator($csm);

            $validator->setLogger($this->logger);

            return $validator;
        });
    }

    /**
     * If extensions are used, the value may need to be checked by this object.
     * We are not using extensions at this time, so we'll just use a base object.
     *
     * @todo Add a way to register custom extension checkers through here.
     *
     * @see https://webauthn-doc.spomky-labs.com/pure-php/advanced-behaviours/extensions
     */
    protected function extensionChecker(): ExtensionOutputCheckerHandler
    {
        return once(function () {
            return ExtensionOutputCheckerHandler::create();
        });
    }

    /**
     * The RP Entity i.e. the application.
     *
     * @see https://webauthn-doc.spomky-labs.com/prerequisites/the-relying-part
     */
    protected function getRelyingParty(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create(
            name: config('profile-filament.webauthn.relying_party.name'),
            id: parse_url(config('profile-filament.webauthn.relying_party.id'), PHP_URL_HOST),
            icon: config('profile-filament.webauthn.relying_party.icon'),
        );
    }

    /**
     * A user entity represents a user in the Webauthn context.
     *
     * @see https://webauthn-doc.spomky-labs.com/prerequisites/user-entity-repository
     */
    protected function getUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        return PublicKeyCredentialUserEntity::create(
            name: $username = $this->model::getUsername($user),
            id: (string) ($this->model::getUserHandle($user) ?? $username),
            displayName: $username,
        );
    }
}
