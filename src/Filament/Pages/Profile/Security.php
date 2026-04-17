<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;
use Rawilk\ProfileFilament\Livewire\MultiFactorAuthenticationManager;
use Rawilk\ProfileFilament\Livewire\UpdatePassword;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read PageConfiguration\SecurityConfiguration|null $configurationInstance
 */
class Security extends ProfilePage
{
    use Concerns\HasProfileConfigurations;

    protected static ?string $configurationClass = PageConfiguration\SecurityConfiguration::class;

    #[Computed]
    public function configurationInstance(): ?PageConfiguration\SecurityConfiguration
    {
        return static::getConfiguration();
    }

    protected static function getDefaultNavigationLabel(): ?string
    {
        return __('profile-filament::pages/security.title');
    }

    protected static function getDefaultNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedShieldExclamation;
    }

    protected static function getDefaultNavigationSort(): ?int
    {
        return 20;
    }

    protected static function getDefaultTitle(): null|string|Htmlable
    {
        return __('profile-filament::pages/security.title');
    }

    protected function defaultLivewireComponents(): array
    {
        $components = [];

        if ($this->showUpdatePasswordForm()) {
            $components[] = UpdatePassword::class;
        }

        if ($this->showMultiFactorForm()) {
            $components[] = $this->getMultiFactorManagerClass();
        }

        return $components;
    }

    protected function showUpdatePasswordForm(): bool
    {
        return $this->configurationInstance?->shouldShowUpdatePasswordForm() ?? true;
    }

    protected function showMultiFactorForm(): bool
    {
        if (! filament(ProfileFilamentPlugin::PLUGIN_ID)->hasMultiFactorAuthentication()) {
            return false;
        }

        return $this->configurationInstance?->shouldShowMultiFactorForm() ?? true;
    }

    protected function getMultiFactorManagerClass(): string
    {
        return $this->configurationInstance?->getMultiFactorManagerClass() ?? MultiFactorAuthenticationManager::class;
    }
}
