<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MarkMultiFactorEnabledAction as MarkMultiFactorEnabledContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Events\MultiFactorAuthenticationWasEnabled;

class MarkMultiFactorEnabledAction implements MarkMultiFactorEnabledContract
{
    public function __invoke(User $user)
    {
        if (! ($user instanceof HasMultiFactorAuthentication)) {
            throw new LogicException('User model must implement the [' . HasMultiFactorAuthentication::class . '] interface to use this action.');
        }

        if ($user->hasMultiFactorAuthenticationEnabled()) {
            return;
        }

        $user->toggleMultiFactorAuthenticationStatus(true);

        MultiFactorAuthenticationWasEnabled::dispatch($user);
    }
}
