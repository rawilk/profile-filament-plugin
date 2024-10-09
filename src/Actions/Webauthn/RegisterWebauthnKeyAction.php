<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Arr;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Contracts\Webauthn\RegisterWebauthnKeyAction as RegisterWebauthnKeyActionContract;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyRegistered;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;

class RegisterWebauthnKeyAction implements RegisterWebauthnKeyActionContract
{
    public function __invoke(
        User $user,
        PublicKeyCredentialSource $publicKeyCredentialSource,
        array $attestation,
        string $keyName,
    ): WebauthnKey {
        $webauthnKey = app(config('profile-filament.models.webauthn_key'))::fromPublicKeyCredentialSource(
            source: $publicKeyCredentialSource,
            user: $user,
            keyName: $keyName,
            attachmentType: Arr::get($attestation, 'authenticatorAttachment'),
        );

        return tap($webauthnKey, function (WebauthnKey $webauthnKey) use ($user) {
            $webauthnKey->save();

            app(MarkTwoFactorEnabledAction::class)($user);

            WebauthnKeyRegistered::dispatch($webauthnKey, $user);
        });
    }
}
