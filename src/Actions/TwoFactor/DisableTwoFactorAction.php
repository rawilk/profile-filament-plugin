<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Contracts\TwoFactor\DisableTwoFactorAction as DisableTwoFactorActionContract;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorDisabledAction as MarkTwoFactorDisabledActionContract;

class DisableTwoFactorAction implements DisableTwoFactorActionContract
{
    public function __invoke(User $user)
    {
        $user->authenticatorApps()->delete();
        $user->webauthnKeys()->delete();

        app(MarkTwoFactorDisabledActionContract::class)($user);
    }
}
