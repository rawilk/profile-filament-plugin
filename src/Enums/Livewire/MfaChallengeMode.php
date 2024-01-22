<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums\Livewire;

use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Auth\Authenticatable as User;

enum MfaChallengeMode: string
{
    case App = 'app';
    case Webauthn = 'webauthn';
    case RecoveryCode = 'code';

    public function icon(): string
    {
        return match ($this) {
            self::App => FilamentIcon::resolve('mfa::totp') ?? 'heroicon-o-device-phone-mobile',
            self::Webauthn => FilamentIcon::resolve('mfa::webauthn') ?? 'heroicon-o-shield-exclamation',
            self::RecoveryCode => FilamentIcon::resolve('mfa::recovery-codes') ?? 'heroicon-o-key',
        };
    }

    public function formHeading(): string
    {
        return match ($this) {
            self::App => __('profile-filament::pages/mfa.totp.heading'),
            self::Webauthn => __('profile-filament::pages/mfa.webauthn.heading'),
            self::RecoveryCode => __('profile-filament::pages/mfa.recovery_code.heading'),
        };
    }

    public function formLabel(?User $user = null): string
    {
        return match ($this) {
            self::App => __('profile-filament::pages/mfa.totp.label'),
            self::Webauthn => $user?->hasPasskeys()
                ? __('profile-filament::pages/mfa.webauthn.label_including_passkeys')
                : __('profile-filament::pages/mfa.webauthn.label'),
            self::RecoveryCode => __('profile-filament::pages/mfa.recovery_code.label'),
        };
    }

    public function alternativeHeading(): string
    {
        return match ($this) {
            self::App => __('profile-filament::pages/mfa.totp.alternative_heading'),
            self::Webauthn => __('profile-filament::pages/mfa.webauthn.alternative_heading'),
            self::RecoveryCode => __('profile-filament::pages/mfa.recovery_code.alternative_heading'),
        };
    }

    public function linkLabel(?User $user = null): string
    {
        return match ($this) {
            self::App => __('profile-filament::pages/mfa.totp.use_label'),
            self::Webauthn => $user?->hasPasskeys()
                ? __('profile-filament::pages/mfa.webauthn.use_label_including_passkeys')
                : __('profile-filament::pages/mfa.webauthn.use_label'),
            self::RecoveryCode => __('profile-filament::pages/mfa.recovery_code.use_label'),
        };
    }
}
