<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile\PageConfiguration;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SettingsConfiguration extends ProfilePageConfiguration
{
    protected function getDefaultNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedCog6Tooth;
    }

    protected function getDefaultNavigationLabel(): ?string
    {
        return __('profile-filament::pages/settings.title');
    }

    protected function getDefaultNavigationSort(): ?int
    {
        return 10;
    }

    protected function getDefaultTitle(): ?string
    {
        return __('profile-filament::pages/settings.title');
    }
}
