<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next)
    {
        $user = Filament::auth()->user();

        if (! $user instanceof HasMultiFactorAuthentication) {
            return $next($request);
        }

        if ($user->hasMultiFactorAuthenticationEnabled()) {
            return $next($request);
        }

        return redirect()->guest(
            filament(ProfileFilamentPlugin::PLUGIN_ID)->getSetUpRequiredMultiFactorAuthenticationUrl()
        );
    }
}
