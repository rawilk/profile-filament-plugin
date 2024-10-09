<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns\Sudo;

use Filament\Actions\Action;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

trait UsesSudoChallengeAction
{
    protected function ensureSudoIsActive(?string $method = null, array $data = [], ?string $caller = null): bool
    {
        if (! $this->sudoModeIsAllowed()) {
            return true;
        }

        if (Sudo::isActive()) {
            // Simple extend it when performing another sensitive action while in sudo mode.
            Sudo::extend();

            return true;
        }

        $this->dispatch('check-sudo', caller: $caller ?? $this::class, method: $method, data: $data);

        return false;
    }

    protected function sudoModeIsAllowed(): bool
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->hasSudoMode();
    }
}
