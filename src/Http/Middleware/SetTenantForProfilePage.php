<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides Filament with a tenant solely while rendering global profile pages.
 *
 * Profile routes remain outside Filament's tenant route group and tenant middleware,
 * but the standard panel sidebar and tenant switcher still require a current tenant
 * to generate their URLs. This middleware resolves that navigation context without
 * making the profile routes themselves tenant-aware.
 */
class SetTenantForProfilePage
{
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel?->hasTenancy()) {
            return $next($request);
        }

        $originalTenant = Filament::getTenant();
        $originalPanel = $panel;
        $user = Filament::auth()->user();
        $tenant = ProfileFilament::plugin()->resolveTenant($user);

        if ($tenant) {
            Filament::setTenant($tenant, isQuiet: true);
        } else {
            Filament::setCurrentPanel(
                (clone $panel)
                    ->homeUrl($request->url())
                    ->navigation(false)
                    ->tenant(null)
                    ->tenantMenu(false),
            );
        }

        try {
            return $next($request);
        } finally {
            Filament::setTenant($originalTenant, isQuiet: true);
            Filament::setCurrentPanel($originalPanel);
        }
    }
}
