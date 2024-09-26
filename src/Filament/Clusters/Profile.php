<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Clusters;

use Filament\Clusters\Cluster;
use Rawilk\ProfileFilament\Concerns;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class Profile extends Cluster
{
    use Concerns\HasPanelClusterRoutes;
    use Concerns\HasPanelSlugs;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    // Fallback
    public static function getSlug(): string
    {
        return (string) rescue(
            callback: fn () => filament(ProfileFilamentPlugin::PLUGIN_ID)->getClusterSlug(),
            rescue: fn () => 'profile',
            report: false,
        );
    }
}
