<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Filament\ChallengePipes;

use Closure;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns\HasAuthChecks;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns\ThrowsFailedEvents;
use Rawilk\ProfileFilament\Auth\Multifactor\Facades\Mfa;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\Dto\MultiFactorEventBagContract;

class AuthenticateUser
{
    use HasAuthChecks;
    use ThrowsFailedEvents;

    public function __invoke(MultiFactorEventBagContract $request, Closure $next)
    {
        $authGuard = $request->getAuthGuard();
        $user = $request->user();

        if (! $this->shouldLogin($user, $authGuard)) {
            $this->fireFailedEvent($authGuard, $user, credentials: []);
            $this->throwFailureValidationException(validationKey: 'multiFactorError');
        }

        $authGuard->login(
            user: $user,
            remember: $request->shouldRememberUser(),
        );

        Mfa::confirmUserSession($user);

        return $next($request);
    }
}
