<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\Passkeys;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Rawilk\ProfileFilament\Contracts\Passkeys\RegisterPasskeyAction as RegisterPasskeyActionContract;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyRegistered;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;

class RegisterPasskeyAction implements RegisterPasskeyActionContract
{
    /** @var class-string<Model> */
    protected string $model;

    public function __construct()
    {
        $this->model = config('profile-filament.models.webauthn_key');
    }

    public function __invoke(
        User $user,
        PublicKeyCredentialSource $publicKeyCredentialSource,
        array $attestation,
        string $keyName,
    ): WebauthnKey {
        $passkey = $this->model::fromPublicKeyCredentialSource(
            source: $publicKeyCredentialSource,
            user: $user,
            keyName: $keyName,
            attachmentType: Arr::get($attestation, 'authenticatorAttachment'),
        );

        return tap($passkey, function (WebauthnKey $passkey) use ($user) {
            $passkey->is_passkey = true;
            $passkey->save();

            cache()->forget($user::hasPasskeysCacheKey($user));

            app(MarkTwoFactorEnabledAction::class)($user);

            PasskeyRegistered::dispatch($passkey, $user);
        });
    }
}
