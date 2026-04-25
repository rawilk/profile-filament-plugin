<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class RequiresSudoMode
{
    public function handle(Request $request, Closure $next)
    {
        if (! $this->panelAllowsSudoMode()) {
            return $next($request);
        }

        if ($this->shouldChallengeForSudo()) {
            SudoModeChallenged::dispatch($request->user(), $request);

            return redirect()->guest($this->getRedirectUrl());
        }

        if (Sudo::isActive()) {
            Sudo::extend();
        }

        return $next($request);
    }

    protected function getRedirectUrl(): string
    {
        return Filament::getCurrentOrDefaultPanel()->route('auth.sudo-challenge');
    }

    protected function shouldChallengeForSudo(): bool
    {
        return ! Sudo::isActive();
    }

    protected function panelAllowsSudoMode(): bool
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if (! $panel->hasPlugin(ProfileFilamentPlugin::PLUGIN_ID)) {
            return false;
        }

        return $panel->getPlugin(ProfileFilamentPlugin::PLUGIN_ID)->hasSudoMode();
    }
}
