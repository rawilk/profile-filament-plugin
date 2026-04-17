<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Filament\ChallengePipes;

use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\Dto\MultiFactorEventBagContract;
use Rawilk\ProfileFilament\Facades\Mfa;

class GuardAgainstExpiredPasswordConfirmation
{
    public function __invoke(MultiFactorEventBagContract $request, Closure $next)
    {
        if (Mfa::passwordConfirmationHasExpired()) {
            Notification::make()
                ->danger()
                ->title(__('profile-filament::auth/multi-factor/challenge/challenge.messages.password-confirmation-expired'))
                ->send();

            redirect()->to(Filament::getLoginUrl());

            return;
        }

        return $next($request);
    }
}
