<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable as User;

interface MarkTwoFactorEnabledAction
{
    public function __invoke(User $user);
}
