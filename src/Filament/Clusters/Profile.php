<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Clusters;

use Filament\Clusters\Cluster;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class Profile extends Cluster
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getSlug(): string
    {
        /** @phpstan-ignore-next-line */
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->getClusterSlug();
    }
}
