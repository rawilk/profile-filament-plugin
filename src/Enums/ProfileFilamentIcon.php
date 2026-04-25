<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums;

use BackedEnum;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;

enum ProfileFilamentIcon: string
{
    case Help = 'profile-filament::help';
    case LogoutSessionModalIcon = 'profile-filament::logout-session-modal';
    case MfaEmail = 'mfa::email';
    case MfaTotp = 'mfa::totp';
    case MfaRecoveryCodes = 'mfa::recovery-codes';
    case MfaWebauthn = 'mfa::webauthn';
    case MfaWebauthnUnsupported = 'mfa::webauthn-unsupported';
    case PendingEmailInfo = 'profile-filament::pending-email-info';
    case SessionDesktop = 'session::desktop';
    case SessionMobile = 'session::mobile';
    case SudoChallenge = 'sudo::challenge';

    public function defaultIcon(): null|string|BackedEnum
    {
        return match ($this) {
            self::Help => Heroicon::OutlinedQuestionMarkCircle,
            self::LogoutSessionModalIcon => Heroicon::OutlinedSignal,
            self::MfaEmail => Heroicon::OutlinedEnvelope,
            self::MfaTotp, self::SessionMobile => Heroicon::OutlinedDevicePhoneMobile,
            self::MfaRecoveryCodes => Heroicon::OutlinedKey,
            self::MfaWebauthn => 'pf-passkey',
            self::MfaWebauthnUnsupported => Heroicon::OutlinedExclamationCircle,
            self::PendingEmailInfo => Heroicon::OutlinedInformationCircle,
            self::SessionDesktop => Heroicon::OutlinedComputerDesktop,
            self::SudoChallenge => Heroicon::FingerPrint,
            default => null,
        };
    }

    public function resolve(): null|string|BackedEnum
    {
        return FilamentIcon::resolve($this->value) ?? $this->defaultIcon();
    }
}
