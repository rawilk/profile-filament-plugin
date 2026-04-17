<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Rawilk\ProfileFilament\Livewire\Sessions\SessionManager;

class Sessions extends ProfilePage
{
    use Concerns\HasProfileConfigurations;

    protected static ?string $configurationClass = PageConfiguration\SessionsConfiguration::class;

    /**
     * Default slug for the session manager page.
     */
    protected static ?string $slug = 'sessions';

    protected static function getDefaultNavigationLabel(): ?string
    {
        return __('profile-filament::pages/sessions.title');
    }

    protected static function getDefaultNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedSignal;
    }

    protected static function getDefaultNavigationSort(): ?int
    {
        return 30;
    }

    protected static function getDefaultTitle(): null|string|Htmlable
    {
        return __('profile-filament::pages/sessions.title');
    }

    protected function defaultLivewireComponents(): array
    {
        return [
            SessionManager::class,
        ];
    }
}
