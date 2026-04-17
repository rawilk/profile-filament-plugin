<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Concerns;

use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

trait InteractsWithSudo
{
    protected function panelAllowsSudoMode(): bool
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->hasSudoMode();
    }

    protected function sudoModeIsActive(): bool
    {
        return Sudo::isActive();
    }

    protected function extendSudo(): void
    {
        if (! $this->sudoModeIsActive()) {
            return;
        }

        Sudo::extend();
    }

    protected function shouldChallengeForSudo(): bool
    {
        if (! $this->panelAllowsSudoMode()) {
            return false;
        }

        return ! $this->sudoModeIsActive();
    }
}
