<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums;

enum RenderHook: string
{
    case MfaSettingsBefore = 'profile-filament::mfa.settings.before';
    case MfaMethodsAfter = 'profile-filament::mfa.methods.after';
}
