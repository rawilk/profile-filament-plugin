<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns;

use Closure;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\SudoChallengeAction;
use Rawilk\ProfileFilament\Auth\Sudo\Concerns\InteractsWithSudo;
use Rawilk\ProfileFilament\Auth\Sudo\Events\SudoModeChallengeWasPresented;

/**
 * This trait is for actions that require a modal of some sort before executing the action.
 */
trait RequiresSudoChallenge
{
    use InteractsWithSudo;

    protected function registerSudoChallenge(?Closure $executeAfterSudo = null): void
    {
        $this->before(function (HasActions $livewire, Request $request) {
            if ($callback = $this->getBeforeCallback()) {
                $this->evaluate($callback);
            }

            if (! $this->shouldChallengeForSudo()) {
                return;
            }

            SudoModeChallengeWasPresented::dispatch(Filament::auth()->user(), $request);

            $livewire->mountAction('sudoChallenge');
        });

        $this->registerModalActions([
            SudoChallengeAction::make()
                ->when(
                    $executeAfterSudo !== null,
                    fn (SudoChallengeAction $action) => $action->executeAfterSudo($executeAfterSudo),
                ),
        ]);

        $this->mountUsing(function (HasActions $livewire, Request $request) {
            if ($callback = $this->getMountUsingCallback()) {
                $this->evaluate($callback);
            }

            if (! $this->shouldChallengeForSudo()) {
                $this->extendSudo();

                return;
            }

            SudoModeChallengeWasPresented::dispatch(Filament::auth()->user(), $request);

            $livewire->mountAction('sudoChallenge');
        });
    }

    protected function getBeforeCallback(): ?Closure
    {
        return null;
    }

    protected function getMountUsingCallback(): ?Closure
    {
        return null;
    }
}
