<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums;

enum ProfileFilamentIcon: string
{
    case Help = 'profile-filament::help';
    case LogoutSessionModalIcon = 'profile-filament::logout-session-modal';
    case MfaEmail = 'mfa::email';
    case MfaTotp = 'mfa::totp';
    case MfaRecoveryCodes = 'mfa::recovery-codes';
    case MfaWebauthn = 'mfa::webauthn';
    case PendingEmailInfo = 'profile-filament::pending-email-info';
    case SessionDesktop = 'session::desktop';
    case SessionMobile = 'session::mobile';
    case SudoChallenge = 'sudo::challenge';
}
