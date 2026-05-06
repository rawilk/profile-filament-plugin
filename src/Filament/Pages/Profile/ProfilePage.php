<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Pages\PageConfiguration;
use Filament\Panel;
use Livewire\Attributes\Computed;
use Rawilk\ProfileFilament\Filament\Clusters\ProfileCluster;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read array<int, \Livewire\Component> $livewireComponents
 */
abstract class ProfilePage extends Page
{
    protected static ?string $cluster = ProfileCluster::class;

    protected string $view = 'profile-filament::filament.clusters.profile-page';

    public static function getRouteName(?Panel $panel = null): string
    {
        $panel ??= Filament::getCurrentOrDefaultPanel();

        if ($configuration = static::resolveConfiguration($panel)) {
            $routeName = 'pages.' . static::getRelativeRouteNameFromConfiguration($configuration);
            $routeName = static::prependClusterRouteBaseName($panel, $routeName);

            return $panel->generateRouteName($routeName);
        }

        return parent::getRouteName($panel);
    }

    public static function resolveConfiguration(?Panel $panel = null): ?PageConfiguration
    {
        $panel ??= Filament::getCurrentOrDefaultPanel();

        return $panel->getPlugin(ProfileFilamentPlugin::PLUGIN_ID)->getPageConfiguration(static::class);
    }

    public static function getRelativeRouteNameFromConfiguration(PageConfiguration $configuration): string
    {
        return (string) str(static::getSlugFromConfiguration($configuration))->replace('/', '.');
    }

    public static function getSlugFromConfiguration(PageConfiguration $configuration): string
    {
        if (filled($configSlug = $configuration->getSlug())) {
            return $configSlug;
        }

        return static::getDefaultSlug() . '/' . $configuration->getKey();
    }

    #[Computed]
    public function pageConfiguration(): ?PageConfiguration
    {
        return static::getConfiguration();
    }

    #[Computed]
    public function livewireComponents(): array
    {
        return $this->getPageConfigurationComponents() ?? $this->defaultLivewireComponents();
    }

    protected function defaultLivewireComponents(): array
    {
        return [];
    }

    protected function getPageConfigurationComponents(): ?array
    {
        if (! $this->pageConfiguration) {
            return null;
        }

        $components = $this->pageConfiguration->getLivewireComponents();

        if (! is_array($components)) {
            return null;
        }

        if ($this->pageConfiguration->shouldMergeLivewireComponents()) {
            return [
                ...$this->defaultLivewireComponents(),
                ...$components,
            ];
        }

        return $components;
    }
}
