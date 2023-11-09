<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Rawilk\ProfileFilament\Support\RecoveryCode;

/**
 * @property bool $two_factor_enabled
 * @property string|null $two_factor_recovery_codes
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait TwoFactorAuthenticatable
{
    public static function hasPasskeysCacheKey(User $user): string
    {
        return "user:{$user->getAuthIdentifier()}:has-passkeys";
    }

    public function recoveryCodes(): array
    {
        return json_decode(
            Crypt::decryptString($this->two_factor_recovery_codes),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function replaceRecoveryCode(string $code): string
    {
        $newCode = RecoveryCode::generate();

        $this->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(
                str_replace(
                    $code,
                    $newCode,
                    Crypt::decryptString($this->two_factor_recovery_codes),
                )
            ),
        ])->save();

        return $newCode;
    }

    public function authenticatorApps(): HasMany
    {
        return $this->hasMany(config('profile-filament.models.authenticator_app'))
            ->latest();
    }

    public function webauthnKeys(): HasMany
    {
        return $this->hasMany(config('profile-filament.models.webauthn_key'))
            ->latest();
    }

    public function nonPasskeyWebauthnKeys(): HasMany
    {
        return $this->webauthnKeys()->where('is_passkey', false);
    }

    public function passkeys(): HasMany
    {
        return $this->webauthnKeys()->where('is_passkey', true);
    }

    public function hasPasskeys(): bool
    {
        return cache()->remember(
            key: static::hasPasskeysCacheKey($this),
            ttl: now()->addHour(),
            callback: fn () => $this->passkeys()->exists(),
        );
    }

    protected function twoFactorEnabled(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (bool) $value,
        );
    }
}
