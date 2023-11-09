<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Facades\Sudo;

class RequiresSudoMode
{
    public function handle(Request $request, Closure $next)
    {
        if (Sudo::isActive()) {
            Sudo::extend();

            return $next($request);
        }

        SudoModeChallenged::dispatch($request->user(), $request);

        Sudo::deactivate();

        return redirect()->guest($this->getRedirectUrl($request));
    }

    protected function getRedirectUrl(Request $request): string
    {
        $panelId = filament()->getCurrentPanel()?->getId();

        if (filament()->hasTenancy() && $tenantId = $request->route()?->parameter('tenant')) {
            return route("filament.{$panelId}.auth.sudo-challenge", ['tenant' => $tenantId]);
        }

        return route("filament.{$panelId}.auth.sudo-challenge");
    }
}
