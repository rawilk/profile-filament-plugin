<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Sudo\Concerns;

use Closure;
use Filament\Actions\MountableAction;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Tables\Actions\Action as TableAction;
use Livewire\Component;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\SudoChallengeAction;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\SudoChallengeFormAction;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\SudoChallengeInfolistAction;
use Rawilk\ProfileFilament\Filament\Actions\Sudo\SudoChallengeTableAction;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

trait RequiresSudo
{
    protected ?Closure $sudoModePrecheck = null;

    public function beforeSudoModeCheck(?Closure $callback = null): static
    {
        $this->sudoModePrecheck = $callback;

        return $this;
    }

    protected function getSudoChallengeAction(bool $hasModal = true): MountableAction
    {
        return match (true) {
            $this instanceof InfolistAction => SudoChallengeInfolistAction::make(),
            $this instanceof TableAction => SudoChallengeTableAction::make(),
            $this instanceof FormAction => SudoChallengeFormAction::make(),
            default => SudoChallengeAction::make(),
        };
    }

    protected function mountSudoAction(Component $livewire, array $arguments = []): void
    {
        if (! $this->sudoModeIsAllowed()) {
            return;
        }

        $preCheck = $this->evaluate($this->sudoModePrecheck);
        if ($preCheck === false) {
            return;
        }

        if (Sudo::isActive()) {
            Sudo::extend();

            return;
        }

        if ($this instanceof InfolistAction) {
            $livewire->mountInfolistAction('sudoChallenge');

            $this->halt();
        }

        if ($this instanceof TableAction) {
            $livewire->mountTableAction('sudoChallenge', arguments: [
                ...$arguments,
                'hasParentModal' => $this->shouldOpenModal(),
                '_action' => $this->getName(),
            ]);

            $this->halt();
        }

        $livewire->mountAction('sudoChallenge', [
            ...$arguments,
            'hasParentModal' => $this->shouldOpenModal(),
            '_action' => $this->getName(),
        ]);

        $this->halt();
    }

    protected function ensureSudoIsActive(Component $livewire): void
    {
        if (! $this->sudoModeIsAllowed()) {
            return;
        }

        if (! Sudo::isActive()) {
            $this->mountSudoAction($livewire);
        }
    }

    protected function sudoModeIsAllowed(): bool
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->hasSudoMode();
    }
}
