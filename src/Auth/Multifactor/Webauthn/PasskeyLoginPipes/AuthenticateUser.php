<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\PasskeyLoginPipes;

use Closure;
use Illuminate\Support\Timebox;
use Rawilk\ProfileFilament\Auth\Exceptions\LoginException;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns\HasAuthChecks;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns\ThrowsFailedEvents;
use Rawilk\ProfileFilament\Auth\Multifactor\Facades\Mfa;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Dto\PasskeyLoginEventBagContract;
use Rawilk\ProfileFilament\Support\Config;

class AuthenticateUser
{
    use HasAuthChecks;
    use ThrowsFailedEvents;

    public function __invoke(PasskeyLoginEventBagContract $request, Closure $next)
    {
        $authGuard = $request->getAuthGuard();
        $user = $request->user();
        $shouldRemember = $request->shouldRememberUser();

        app(Timebox::class)->call(function (Timebox $timebox) use ($user, $authGuard, $shouldRemember) {
            $this->fireAttemptingEvent($authGuard, [], $shouldRemember);

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

            $timebox->returnEarly();
        }, microseconds: Config::getTimeboxDuration());

        $authGuard->login(
            user: $user,
            remember: $shouldRemember,
        );

        Mfa::confirmUserSession($user);

        return $next($request);
    }
}
