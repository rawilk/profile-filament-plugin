<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Clusters\Profile;

use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Rawilk\ProfileFilament\Filament\Clusters\Profile;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read array<string, \Livewire\Component> $registeredComponents
 */
abstract class ProfilePage extends Page
{
    protected static ?string $cluster = Profile::class;

    protected static string $view = 'profile-filament::filament.clusters.profile-page';

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): ?string
    {
        /** @phpstan-ignore-next-line */
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->getIcon(static::class);
    }

    public static function getNavigationGroup(): ?string
    {
        /** @phpstan-ignore-next-line */
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->pageGroup(static::class);
    }

    public static function getNavigationSort(): ?int
    {
        /** @phpstan-ignore-next-line */
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->pageSort(static::class);
    }

    public static function getSlug(): string
    {
        return (string) rescue(
            /** @phpstan-ignore-next-line */
            callback: fn (): string => filament(ProfileFilamentPlugin::PLUGIN_ID)->getSlug(static::class),
            rescue: fn (): string => '#',
            report: false,
        );
    }

    public static function shouldRegisterNavigation(): bool
    {
        /** @phpstan-ignore-next-line */
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->isEnabled(static::class);
    }

    public function getBreadcrumb(): ?string
    {
        /** @phpstan-ignore-next-line */
        return static::$breadcrumb ?? static::getNavigationLabel();
    }

    public function getBreadcrumbs(): array
    {
        /** @phpstan-ignore-next-line */
        $rootPage = filament(ProfileFilamentPlugin::PLUGIN_ID)->getRootProfilePage();

        $breadcrumb = $this->isRootProfilePage() ? null : $this->getBreadcrumb();

        return [
            ...(filled($rootPage) ? [$rootPage::getUrl() => app($rootPage)->getBreadcrumb()] : []),
            ...(filled($breadcrumb) ? [$breadcrumb] : []),
        ];
    }

    #[Computed]
    public function registeredComponents(): Collection
    {
        /** @phpstan-ignore-next-line */
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->componentsFor(static::class);
    }

    protected function isRootProfilePage(): bool
    {
        /** @phpstan-ignore-next-line */
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->isRootProfilePage(static::class);
    }
}
