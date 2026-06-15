<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Emails\Concerns;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;

trait RateLimitsResendPendingEmailChange
{
    public function rateLimitKey(?Authenticatable $user = null): string
    {
        $user ??= Filament::auth()->user();

        return 'resendPendingUserEmail:' . $user->getAuthIdentifier();
    }
}
