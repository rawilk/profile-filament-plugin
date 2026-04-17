<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Panel;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class ProfileCluster extends Cluster
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return (string) rescue(
            callback: fn () => $panel->getPlugin(ProfileFilamentPlugin::PLUGIN_ID)->getProfileClusterSlug(),
            rescue: fn () => 'profile',
        );
    }

    public function mount(): void
    {
        $url = filament(ProfileFilamentPlugin::PLUGIN_ID)->getDefaultProfilePageUrl();
        if (filled($url)) {
            redirect($url);

            return;
        }

        parent::mount();
    }
}
