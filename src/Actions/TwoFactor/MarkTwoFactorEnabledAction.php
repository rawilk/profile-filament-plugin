<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable as User;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorEnabledAction as MarkTwoFactorEnabledActionContract;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;

class MarkTwoFactorEnabledAction implements MarkTwoFactorEnabledActionContract
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

        TwoFactorAuthenticationWasEnabled::dispatch($user);
    }
}
