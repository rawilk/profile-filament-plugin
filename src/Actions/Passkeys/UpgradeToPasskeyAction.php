<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\Passkeys;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Arr;
use Rawilk\ProfileFilament\Contracts\Passkeys\UpgradeToPasskeyAction as UpgradeToPasskeyActionContract;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUpgradeToPasskey;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;

class UpgradeToPasskeyAction implements UpgradeToPasskeyActionContract
{
    public function __invoke(
        User $user,
        PublicKeyCredentialSource $publicKeyCredentialSource,
        array $attestation,
        WebauthnKey $webauthnKey,
    ): WebauthnKey {
        $passkey = app(config('profile-filament.models.webauthn_key'))::fromPublicKeyCredentialSource(
            source: $publicKeyCredentialSource,
            user: $user,
            keyName: $webauthnKey->name,
            attachmentType: Arr::get($attestation, 'authenticatorAttachment'),
        );

        return tap($passkey, function (WebauthnKey $passkey) use ($webauthnKey, $user) {
            $passkey->is_passkey = true;
            $passkey->save();

            $webauthnKey->delete();

            cache()->forget($user::hasPasskeysCacheKey($user));

            WebauthnKeyUpgradeToPasskey::dispatch($user, $passkey, $webauthnKey);
        });
    }
}
