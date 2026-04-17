<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums;

enum RenderHook: string
{
    /** @deprecated  */
    case MfaSettingsBefore = 'profile-filament::mfa.settings.before';
    /** @deprecated */
    case MfaMethodsAfter = 'profile-filament::mfa.methods.after';
    case MfaChallengeStart = 'profile-filament::mfa-challenge.start';
}
