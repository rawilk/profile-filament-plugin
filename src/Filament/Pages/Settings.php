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
class Settings extends Page
{
    use IsProfilePage;

    protected static string $view = 'profile-filament::pages.settings';

    public static function getNavigationLabel(): string
    {
        return __('profile-filament::pages/settings.title');
    }

    public static function getNavigationIcon(): ?string
    {
        return filament(ProfileFilamentPlugin::make()->getId())->getIcon(self::class);
    }

    public static function getSlug(): string
    {
        return (string) rescue(
            callback: fn () => filament(ProfileFilamentPlugin::make()->getId())->getSlug(self::class),
            rescue: fn () => '#',
            report: false,
        );
    }

    public static function innerNavSort(): int
    {
        return filament(ProfileFilamentPlugin::make()->getId())->pageSort(self::class);
    }

    public static function innerNavGroup(): ?string
    {
        return filament(ProfileFilamentPlugin::make()->getId())->pageGroup(self::class);
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::pages/settings.title');
    }

    #[Computed]
    public function registeredComponents(): Collection
    {
        return filament(ProfileFilamentPlugin::make()->getId())->componentsFor(self::class);
    }
}
