<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\EmailAuthentication;

use Illuminate\Contracts\Auth\Authenticatable;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MarkMultiFactorEnabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication;
use Rawilk\ProfileFilament\Contracts\EmailAuthentication\EnableEmailAuthenticationAction as EnableEmailAuthenticationContract;
use Rawilk\ProfileFilament\Events\EmailAuthentication\EmailAuthenticationWasEnabled;

class EnableEmailAuthenticationAction implements EnableEmailAuthenticationContract
{
    public function __invoke(Authenticatable $user)
    {
        if (! ($user instanceof HasEmailAuthentication)) {
            throw new LogicException('The user model must implement the [' . HasEmailAuthentication::class . '] interface to use this action');
        }

        if ($user->hasEmailAuthentication()) {
            return;
        }

        $user->toggleEmailAuthentication(true);

        app(MarkMultiFactorEnabledAction::class)($user);

        EmailAuthenticationWasEnabled::dispatch($user);
    }
}
