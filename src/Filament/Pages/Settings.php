<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Rawilk\ProfileFilament\Concerns\IsProfilePage;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

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
}
