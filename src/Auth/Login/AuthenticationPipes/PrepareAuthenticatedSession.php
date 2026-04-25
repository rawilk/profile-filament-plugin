<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes;

use Closure;
use Rawilk\ProfileFilament\Auth\Login\Dto\LoginEventBagContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\Dto\MultiFactorEventBagContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Dto\PasskeyLoginEventBagContract;
use Rawilk\ProfileFilament\Events\Sessions\PreparingAuthenticatedSession;
use Rawilk\ProfileFilament\Facades\Mfa;

class PrepareAuthenticatedSession
{
    public function __invoke(
        LoginEventBagContract|MultiFactorEventBagContract|PasskeyLoginEventBagContract $request,
        Closure $next,
    ) {
        Mfa::flushPendingSession();

        PreparingAuthenticatedSession::dispatch($request->user());

        session()->regenerate();

        return $next($request);
    }
}
