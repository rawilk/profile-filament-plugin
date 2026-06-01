<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\PasskeyLoginPipes;

use Closure;
use Rawilk\ProfileFilament\Auth\Exceptions\LoginException;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns\HasAuthChecks;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns\ThrowsFailedEvents;
use Rawilk\ProfileFilament\Auth\Multifactor\Facades\Mfa;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Dto\PasskeyLoginEventBagContract;

class AuthenticateUser
{
    use HasAuthChecks;
    use ThrowsFailedEvents;

    public function __invoke(PasskeyLoginEventBagContract $request, Closure $next)
    {
        $authGuard = $request->getAuthGuard();
        $user = $request->user();

        $error = null;

        try {
            $allowedToLogin = $this->shouldLogin($user, $authGuard);
        } catch (LoginException $exception) {
            $allowedToLogin = false;
            $error = $exception->getMessage();
        }

        if (! $allowedToLogin) {
            $this->fireFailedEvent($authGuard, $user, credentials: []);
            $this->throwFailureValidationException(validationKey: 'passkey', error: $error);
        }

        $authGuard->login(
            user: $user,
            remember: $request->shouldRememberUser(),
        );

        Mfa::confirmUserSession($user);

        return $next($request);
    }
}
