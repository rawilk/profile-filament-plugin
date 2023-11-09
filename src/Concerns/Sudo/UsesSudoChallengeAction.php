<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns\Sudo;

use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Filament\Actions\SudoChallengeAction;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

trait UsesSudoChallengeAction
{
    public array $sudoChallengeData = [];

    #[Locked]
    public ?string $sudoChallengeMode = null;

    public bool $hasSudoWebauthnError = false;

    #[Computed]
    public function sudoChallengeModeEnum(): ?SudoChallengeMode
    {
        if (! $this->sudoChallengeMode) {
            return null;
        }

        return SudoChallengeMode::tryFrom($this->sudoChallengeMode);
    }

    public function sudoChallengeAction(): Action
    {
        return SudoChallengeAction::make('sudoChallenge');
    }

    protected function ensureSudoIsActive(string $returnAction): void
    {
        if (! $this->sudoModeIsAllowed()) {
            return;
        }

        if (! Sudo::isActive()) {
            $this->replaceMountedAction('sudoChallenge', ['returnAction' => $returnAction]);

            throw new Halt;
        }

        // Simply extend sudo mode when performing another sudo action in sudo mode.
        Sudo::extend();
    }

    protected function sudoModeIsAllowed(): bool
    {
        return filament(ProfileFilamentPlugin::make()->getId())->hasSudoMode();
    }
}
