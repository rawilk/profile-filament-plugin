<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Emails\Concerns;

use Filament\Facades\Filament;

trait RateLimitsResendPendingEmailChange
{
    protected function rateLimitKey(): string
    {
        return 'resendPendingUserEmail:' . Filament::auth()->id();
    }
}
