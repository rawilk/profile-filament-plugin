<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\Auth;

use Closure;
use Filament\Facades\Filament;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Mfa;

class PrepareUserSession
{
    /**
     * @param  \Rawilk\ProfileFilament\Dto\Auth\TwoFactorLoginEventBag  $request
     */
    public function handle($request, Closure $next)
    {
        session()->forget(MfaSession::User->value);
        session()->forget(MfaSession::Remember->value);

        Mfa::confirmUserSession($request->user);

        Filament::auth()->login($request->user, $request->remember);

        session()->regenerate();

        return $next($request);
    }
}
