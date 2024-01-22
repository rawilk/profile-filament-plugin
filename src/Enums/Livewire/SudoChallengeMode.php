<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums\Livewire;

use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Auth\Authenticatable as User;

enum SudoChallengeMode: string
{
    case Password = 'password';
    case App = 'app';
    case Webauthn = 'webauthn';

    public function heading(?User $user = null): ?string
    {
        return match ($this) {
            self::App => __('profile-filament::messages.sudo_challenge.totp.heading'),
            self::Webauthn => $user?->hasPasskeys()
                ? __('profile-filament::messages.sudo_challenge.webauthn.heading_including_passkeys')
                : __('profile-filament::messages.sudo_challenge.webauthn.heading'),
            default => null,
        };
    }

    public function icon(): ?string
    {
        return match ($this) {
            self::App => FilamentIcon::resolve('mfa::totp') ?? 'heroicon-o-device-phone-mobile',
            self::Webauthn => FilamentIcon::resolve('mfa::webauthn') ?? 'heroicon-o-shield-exclamation',
            default => null,
        };
    }

    public function linkLabel(?User $user = null): string
    {
        return match ($this) {
            self::Password => __('profile-filament::messages.sudo_challenge.password.use_label'),
            self::App => __('profile-filament::messages.sudo_challenge.totp.use_label'),
            self::Webauthn => $user?->hasPasskeys()
                ? __('profile-filament::messages.sudo_challenge.webauthn.use_label_including_passkeys')
                : __('profile-filament::messages.sudo_challenge.webauthn.use_label'),
        };
    }

    public function actionButton(?User $user = null): string
    {
        return match ($this) {
            self::Password => __('profile-filament::messages.sudo_challenge.password.submit'),
            self::App => __('profile-filament::messages.sudo_challenge.totp.submit'),
            self::Webauthn => $user?->hasPasskeys()
                ? __('profile-filament::messages.sudo_challenge.webauthn.submit_including_passkeys')
                : __('profile-filament::messages.sudo_challenge.webauthn.submit'),
        };
    }
}
