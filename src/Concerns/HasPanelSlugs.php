<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns;

use Filament\Panel;

trait HasPanelSlugs
{
    protected static array $panelSlugs = [];

    public static function registerPanelSlug(string $panelId, string $slug): void
    {
        static::$panelSlugs[$panelId] = $slug;
    }

    public static function getPanelSlug(Panel $panel): string
    {
        return static::$panelSlugs[$panel->getId()] ?? static::getSlug();
    }
}
