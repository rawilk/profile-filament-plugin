<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Rawilk\ProfileFilament\Livewire\DeleteAccount;
use Rawilk\ProfileFilament\Livewire\Emails\UserEmail;

class Settings extends ProfilePage
{
    use Concerns\HasProfileConfigurations;

    protected static ?string $configurationClass = PageConfiguration\SettingsConfiguration::class;

    protected static function getDefaultNavigationLabel(): ?string
    {
        return __('profile-filament::pages/settings.title');
    }

    protected static function getDefaultNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::Cog6Tooth;
    }

    protected static function getDefaultNavigationSort(): ?int
    {
        return 10;
    }

    protected static function getDefaultTitle(): null|string|Htmlable
    {
        return __('profile-filament::pages/settings.title');
    }

    protected function defaultLivewireComponents(): array
    {
        return [
            UserEmail::class,
            DeleteAccount::class,
        ];
    }
}
