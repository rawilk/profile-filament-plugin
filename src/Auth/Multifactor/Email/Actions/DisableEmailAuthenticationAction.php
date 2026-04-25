<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MarkMultiFactorDisabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\DisableEmailAuthenticationAction as DisableEmailAuthenticationContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication;
use Rawilk\ProfileFilament\Events\EmailAuthentication\EmailAuthenticationWasDisabled;

class DisableEmailAuthenticationAction implements DisableEmailAuthenticationContract
{
    public function __invoke(Authenticatable $user)
    {
        if (! ($user instanceof HasEmailAuthentication)) {
            throw new LogicException('The user model must implement the [' . HasEmailAuthentication::class . '] interface to use this action');
        }

        if (! $user->hasEmailAuthentication()) {
            return;
        }

        $user->toggleEmailAuthentication(false);

        app(MarkMultiFactorDisabledAction::class)($user);

        EmailAuthenticationWasDisabled::dispatch($user);
    }
}
