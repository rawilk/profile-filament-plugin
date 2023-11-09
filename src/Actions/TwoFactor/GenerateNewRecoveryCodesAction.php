<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Rawilk\ProfileFilament\Contracts\TwoFactor\GenerateNewRecoveryCodesAction as GenerateNewRecoveryCodesActionContract;
use Rawilk\ProfileFilament\Events\RecoveryCodesRegenerated;
use Rawilk\ProfileFilament\Support\RecoveryCode;

class GenerateNewRecoveryCodesAction implements GenerateNewRecoveryCodesActionContract
{
    public function __invoke(User $user)
    {
        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(
                Collection::times(8, fn () => RecoveryCode::generate())->toJson()
            ),
        ])->save();

        RecoveryCodesRegenerated::dispatch($user);
    }
}
