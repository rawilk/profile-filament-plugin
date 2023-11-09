<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Facades;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Webauthn\PublicKeyCredentialCreationOptions attestationObjectFor(string $username, string|int $userId = null)
 * @method static \Webauthn\PublicKeyCredentialRequestOptions assertionObjectFor(string|int $userId)
 * @method static \Webauthn\PublicKeyCredentialCreationOptions passkeyAttestationObjectFor(string $username, string|int $userId = null, array $excludeCredentials = [])
 * @method static \Webauthn\PublicKeyCredentialRequestOptions passkeyAssertionObject()
 * @method static array verifyAssertion(null|User $user, array $assertionResponse, \Webauthn\PublicKeyCredentialRequestOptions $storedPublicKey, bool $requiresPasskey = false)
 * @method static \Webauthn\PublicKeyCredentialSource verifyAttestation(array $attestationResponse, \Webauthn\PublicKeyCredentialCreationOptions $storedPublicKey)
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
