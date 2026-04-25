<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns;

use Closure;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Rawilk\ProfileFilament\Auth\Sudo\Concerns\InteractsWithSudo;
use Rawilk\ProfileFilament\Auth\Sudo\Events\SudoModeChallengeWasPresented;

/**
 * This trait is for actions that do not require a modal but instead execute the action right away.
 */
trait RequiresSudoChallengeWithoutModal
{
    use InteractsWithSudo;

    protected function registerSudoChallenge(): void
    {
        $this->mountUsing(function (HasActions $livewire, array $arguments, Request $request) {
            if ($callback = $this->getMountUsingCallback()) {
                $this->evaluate($callback);
            }

            if ($this->shouldChallengeForSudo()) {
                // We need the context for actions that are in things like schemas.
                $mountedActionData = Arr::last($livewire->mountedActions);

                $context = $mountedActionData && $mountedActionData['name'] === $this->getName()
                    ? $mountedActionData['context'] ?? []
                    : [];

                $arguments['sudo'] = [
                    'action' => $this->getName(),
                    'context' => $context,
                ];

                SudoModeChallengeWasPresented::dispatch(Filament::auth()->user(), $request);

                $livewire->replaceMountedAction('sudoChallenge', arguments: $arguments);
            }
        });
    }

    protected function getMountUsingCallback(): ?Closure
    {
        return null;
    }
}
