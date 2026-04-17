<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile\PageConfiguration;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ProfileInfoConfiguration extends ProfilePageConfiguration
{
    protected function getDefaultNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedUser;
    }

    protected function getDefaultNavigationLabel(): ?string
    {
        return __('profile-filament::pages/profile.title');
    }

    protected function getDefaultNavigationSort(): ?int
    {
        return 0;
    }

    protected function getDefaultTitle(): ?string
    {
        return __('profile-filament::pages/profile.title');
    }
}
