<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Facades;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Facade;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

/**
 * @method static PublicKeyCredentialCreationOptions attestationObjectFor(User $user)
 * @method static PublicKeyCredentialRequestOptions assertionObjectFor(User $user)
 * @method static PublicKeyCredentialCreationOptions passkeyAttestationObjectFor(User $user, array $excludeCredentials = [])
 * @method static PublicKeyCredentialRequestOptions passkeyAssertionObject()
 * @method static array verifyAssertion(null|User $user, array $assertionResponse, PublicKeyCredentialRequestOptions $storedPublicKey, bool $requiresPasskey = false)
 * @method static PublicKeyCredentialSource verifyAttestation(array $attestationResponse, PublicKeyCredentialCreationOptions $storedPublicKey)
 * @method static string serializePublicKeyCredentialSource(PublicKeyCredentialSource $source)
 * @method static PublicKeyCredentialSource unserializeKeyData(string $json)
 * @method static array serializePublicKeyOptionsForRequest(PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options)
 *
 * @see \Rawilk\ProfileFilament\Services\Webauthn
 */
class Webauthn extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Rawilk\ProfileFilament\Services\Webauthn::class;
    }
}
