<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Route;

trait HasPanelPageRoutes
{
    use HasPanelSlugs;

    public static function registerRoutes(Panel $panel): void
    {
        if (filled(static::getCluster())) {
            Route::name(static::prependPanelClusterRouteBaseName($panel, 'pages.'))
                ->prefix(static::prependPanelClusterSlug($panel, ''))
                ->group(fn () => static::routes($panel));

            return;
        }

        Route::name('pages.')->group(fn () => static::routes($panel));
    }

    public static function routes(Panel $panel): void
    {
        Route::get(static::getPanelRoutePath($panel), static::class)
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name(static::getPanelRelativeRouteName($panel));
    }

    public static function prependPanelClusterRouteBaseName(Panel $panel, string $name): string
    {
        if (filled($cluster = static::getCluster())) {
            return $cluster::prependPanelClusterRouteBaseName($panel, $name);
        }

        return $name;
    }

    public static function prependPanelClusterSlug(Panel $panel, string $slug): string
    {
        if (filled($cluster = static::getCluster())) {
            return $cluster::prependPanelClusterSlug($panel, $slug);
        }

        return $slug;
    }

    public static function getPanelRelativeRouteName(Panel $panel): string
    {
        return (string) str(static::getPanelSlug($panel))->replace('/', '.');
    }

    public static function getPanelRoutePath(Panel $panel): string
    {
        return '/' . static::getPanelSlug($panel);
    }

    public static function getRouteName(?string $panel = null): string
    {
        $panel = $panel ? Filament::getPanel($panel) : Filament::getDefaultPanel();

        $routeName = 'pages.' . static::getPanelRelativeRouteName($panel);
        $routeName = static::prependPanelClusterRouteBaseName($panel, $routeName);

        return $panel->generateRouteName($routeName);
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl(panel: filament()->getId());
    }

    public static function getNavigationItemActiveRoutePattern(): string
    {
        return static::getRouteName(panel: filament()->getId());
    }
}
