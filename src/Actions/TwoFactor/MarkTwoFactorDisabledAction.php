<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable as User;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\HasMultiFactorAuthenticationRecovery;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorDisabledAction as MarkTwoFactorDisabledActionContract;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasDisabled;

class MarkTwoFactorDisabledAction implements MarkTwoFactorDisabledActionContract
{
    /**
     * @param  User&HasMultiFactorAuthentication  $user
     */
    public function __invoke(User $user): void
    {
        if (! ($user instanceof HasMultiFactorAuthentication)) {
            throw new LogicException('User model must implement the [' . HasMultiFactorAuthentication::class . '] interface to use this action.');
        }

        if ($user->hasOtherEnabledMultiFactorProviders()) {
            return;
        }

        $user->toggleMultiFactorAuthenticationStatus(false);
        $user->setPreferredMfaProvider(null);

        if ($user instanceof HasMultiFactorAuthenticationRecovery) {
            $user->saveAuthenticationRecoveryCodes(null);
        }

        TwoFactorAuthenticationWasDisabled::dispatch($user);
    }
}
