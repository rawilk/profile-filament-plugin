<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Livewire\Concerns;

use Filament\Actions\Action;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\SudoChallengeAction;

/**
 * This is mainly for components that contain sensitive actions that
 * do not require a modal. We need to define the sudo challenge action
 * on the component so that the sensitive action can replace
 * its action with the sudo challenge action.
 */
trait NeedsSudoChallengeAction
{
    public function sudoChallengeAction(): Action
    {
        return SudoChallengeAction::make('sudoChallenge');
    }
}
