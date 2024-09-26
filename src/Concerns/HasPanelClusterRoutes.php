<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Route;

trait HasPanelClusterRoutes
{
    public static function routes(Panel $panel): void
    {
        Route::get(static::getPanelRoutePath($panel), static::class)
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name(static::getPanelRelativeRouteName($panel));
    }

    public static function getPanelRoutePath(Panel $panel): string
    {
        return '/' . static::getPanelSlug($panel);
    }

    public static function getPanelRelativeRouteName(Panel $panel): string
    {
        return (string) str(static::getPanelSlug($panel))->replace('/', '.');
    }

    public static function prependPanelClusterRouteBaseName(Panel $panel, string $name): string
    {
        return (string) str(static::getPanelSlug($panel))
            ->replace('/', '.')
            ->append(".{$name}");
    }

    public static function prependPanelClusterSlug(Panel $panel, string $slug): string
    {
        return static::getPanelSlug($panel) . "/{$slug}";
    }

    public static function getRouteName(?string $panel = null): string
    {
        $panel = $panel ? Filament::getPanel($panel) : Filament::getCurrentPanel();

        return $panel->generateRouteName(
            static::getPanelRelativeRouteName($panel)
        );
    }
}
