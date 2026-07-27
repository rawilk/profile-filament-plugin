<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Panel;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Filament\Concerns\HasProfileRoutes;
use Rawilk\ProfileFilament\ProfileFilamentPlugin as ProfilePlugin;

class ProfileCluster extends Cluster
{
    use HasProfileRoutes;

    public static function getClusteredComponents(): array
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $plugin = ProfileFilament::plugin($panel->getId());

        if ($plugin->isTenantAware()) {
            return parent::getClusteredComponents();
        }

        return $plugin->getProfilePageClasses();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        $panel ??= Filament::getCurrentOrDefaultPanel();

        if (! $panel->hasPlugin(ProfilePlugin::PLUGIN_ID)) {
            return 'profile';
        }

        return ProfileFilament::plugin($panel->getId())->getProfileClusterSlug();
    }

    public function mount(): void
    {
        $url = ProfileFilament::plugin()->getDefaultProfilePageUrl();
        if (filled($url)) {
            redirect($url);

            return;
        }

        parent::mount();
    }
}
