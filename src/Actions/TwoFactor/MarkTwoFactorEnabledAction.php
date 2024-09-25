<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorEnabledAction as MarkTwoFactorEnabledActionContract;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Support\RecoveryCode;

class MarkTwoFactorEnabledAction implements MarkTwoFactorEnabledActionContract
{
    public function __invoke(User $user)
    {
        if ($user->two_factor_enabled) {
            return;
        }

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => Crypt::encryptString(
                Collection::times(8, fn () => RecoveryCode::generate())->toJson()
            ),
        ])->save();

        TwoFactorAuthenticationWasEnabled::dispatch($user);
    }
}
