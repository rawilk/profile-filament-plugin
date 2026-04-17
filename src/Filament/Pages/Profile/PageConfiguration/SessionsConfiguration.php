<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile\PageConfiguration;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SessionsConfiguration extends ProfilePageConfiguration
{
    protected function getDefaultNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedSignal;
    }

    protected function getDefaultNavigationLabel(): ?string
    {
        return __('profile-filament::pages/sessions.title');
    }

    protected function getDefaultNavigationSort(): ?int
    {
        return 30;
    }

    protected function getDefaultTitle(): ?string
    {
        return __('profile-filament::pages/sessions.title');
    }
}
