<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;

trait SetsPreferredMultiFactorProvider
{
    protected function setPreferredMultiFactorProvider(Authenticatable $user, string $providerId): void
    {
        if (! ($user instanceof HasMultiFactorAuthentication)) {
            return;
        }

        if (filled($user->getPreferredMfaProvider())) {
            return;
        }

        $user->setPreferredMfaProvider($providerId);
    }
}
