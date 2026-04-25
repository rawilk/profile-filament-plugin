<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\PasskeyLoginPipes;

use Closure;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns\HasAuthChecks;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns\ThrowsFailedEvents;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Dto\PasskeyLoginEventBagContract;
use Rawilk\ProfileFilament\Facades\Mfa;

class AuthenticateUser
{
    use HasAuthChecks;
    use ThrowsFailedEvents;

    public function __invoke(PasskeyLoginEventBagContract $request, Closure $next)
    {
        $authGuard = $request->getAuthGuard();
        $user = $request->user();

        if (! $this->shouldLogin($user, $authGuard)) {
            $this->fireFailedEvent($authGuard, $user, credentials: []);
            $this->throwFailureValidationException(validationKey: 'passkey');
        }

        $authGuard->login(
            user: $user,
            remember: $request->shouldRememberUser(),
        );

        Mfa::confirmUserSession($user);

        return $next($request);
    }
}
