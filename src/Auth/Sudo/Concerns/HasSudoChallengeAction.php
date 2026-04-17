<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Concerns;

use Filament\Actions\Action;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\SudoChallengeAction;

trait HasSudoChallengeAction
{
    public function sudoChallengeAction(): Action
    {
        return SudoChallengeAction::make();
    }
}
