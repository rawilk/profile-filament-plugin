<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Concerns;

use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

trait InteractsStaticlyWithSudo
{
    protected static function panelAllowsSudoMode(): bool
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->hasSudoMode();
    }

    protected static function sudoModeIsActive(): bool
    {
        return Sudo::isActive();
    }

    protected static function extendSudo(): void
    {
        if (static::sudoModeIsActive()) {
            return;
        }

        Sudo::extend();
    }

    protected static function shouldChallengeForSudo(): bool
    {
        if (! static::panelAllowsSudoMode()) {
            return false;
        }

        return ! static::sudoModeIsActive();
    }
}
