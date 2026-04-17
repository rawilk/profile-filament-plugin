<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes;

use Closure;
use Illuminate\Support\Timebox;
use Rawilk\ProfileFilament\Auth\Login\Dto\LoginEventBagContract;

class ResolveUser
{
    use Concerns\HasAuthChecks;
    use Concerns\ThrowsFailedEvents;

    public function __invoke(LoginEventBagContract $request, Closure $next)
    {
        /**
         * Even though the auth provider uses a timebox call, we are also going to do that
         * for the users that have multifactor authentication enabled, since we are going
         * to return early and redirect to a challenge instead.
         *
         * We are also going to perform the same checks that we do for users without
         * mfa enabled to determine if they are allowed to access the system.
         */
        app(Timebox::class)->call(function (Timebox $timebox) use ($request) {
            $userProvider = $request->getUserProvider();
            $credentials = $request->getCredentialsFromFormData();

            $user = $userProvider->retrieveByCredentials($credentials);

            if ($this->hasValidCredentials($userProvider, $user, $credentials) && $this->shouldLogin($user, $request->getAuthGuard())) {
                $timebox->returnEarly();

                $request->setUser($user);

                return;
            }

            $this->fireFailedEvent($request->getAuthGuard(), $user, $credentials);

            $this->throwFailureValidationException();
        }, microseconds: 200000);

        return $next($request);
    }
}
