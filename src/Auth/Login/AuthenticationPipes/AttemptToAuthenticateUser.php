<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes;

use Closure;
use Rawilk\ProfileFilament\Auth\Login\Dto\LoginEventBagContract;

class AttemptToAuthenticateUser
{
    use Concerns\HasAuthChecks;
    use Concerns\ThrowsFailedEvents;

    public function __invoke(LoginEventBagContract $request, Closure $next)
    {
        $credentials = $request->getCredentialsFromFormData();
        $authGuard = $request->getAuthGuard();

        if (
            ! $authGuard->attemptWhen(
                credentials: $credentials,
                callbacks: $this->getAttemptAuthCallback(),
                remember: $request->shouldRememberUser(),
            )
        ) {
            $this->fireFailedEvent($authGuard, $request->user(), $credentials);
            $this->throwFailureValidationException();
        }

        return $next($request);
    }
}
