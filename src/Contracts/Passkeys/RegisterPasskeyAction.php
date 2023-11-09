<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts\Passkeys;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;

interface RegisterPasskeyAction
{
    public function __invoke(
        User $user,
        PublicKeyCredentialSource $publicKeyCredentialSource,
        array $attestation,
        string $keyName,
    ): WebauthnKey;
}
