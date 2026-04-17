<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo as ProfileInfoComponent;

class ProfileInfo extends ProfilePage
{
    use Concerns\HasProfileConfigurations;

    protected static ?string $configurationClass = PageConfiguration\ProfileInfoConfiguration::class;

    /**
     * Default slug for the profile info page.
     */
    protected static ?string $slug = 'user';

    protected static function getDefaultNavigationLabel(): ?string
    {
        return __('profile-filament::pages/profile.title');
    }

    protected static function getDefaultNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedUser;
    }

    protected static function getDefaultNavigationSort(): ?int
    {
        return 0;
    }

    protected static function getDefaultTitle(): null|string|Htmlable
    {
        return __('profile-filament::pages/profile.title');
    }

    protected function defaultLivewireComponents(): array
    {
        return [
            ProfileInfoComponent::class,
        ];
    }
}
