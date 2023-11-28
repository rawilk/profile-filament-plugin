<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Rawilk\ProfileFilament\Concerns\IsProfilePage;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read array<string, \Livewire\Component> $registeredComponents
 */
class Profile extends Page
{
    use IsProfilePage;

    protected static string $view = 'profile-filament::pages.profile';

    public static function getNavigationLabel(): string
    {
        return __('profile-filament::pages/profile.title');
    }

    public static function getNavigationIcon(): ?string
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->getIcon(self::class);
    }

    public static function getSlug(): string
    {
        return (string) rescue(
            callback: fn () => filament(ProfileFilamentPlugin::PLUGIN_ID)->getSlug(self::class),
            rescue: fn () => '#',
            report: false,
        );
    }

    public static function innerNavGroup(): ?string
    {
        return filament(ProfileFilamentPLugin::PLUGIN_ID)->pageGroup(self::class);
    }

    public static function innerNavSort(): int
    {
        return filament(ProfileFilamentPLugin::PLUGIN_ID)->pageSort(self::class);
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::pages/profile.heading');
    }

    #[Computed]
    public function registeredComponents(): Collection
    {
        return filament(ProfileFilamentPLugin::PLUGIN_ID)->componentsFor(self::class);
    }
}
