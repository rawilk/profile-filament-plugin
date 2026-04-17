<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Profile\PageConfiguration;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Rawilk\ProfileFilament\Livewire\MultiFactorAuthenticationManager;

class SecurityConfiguration extends ProfilePageConfiguration
{
    protected bool $hasUpdatePasswordForm = true;

    protected bool $hasMultiFactorForm = true;

    protected ?string $multiFactorManagerClass = MultiFactorAuthenticationManager::class;

    public function updatePasswordForm(bool $condition = true): static
    {
        $this->hasUpdatePasswordForm = $condition;

        return $this;
    }

    public function manageMultiFactorForm(bool $condition = true, string $managerClass = MultiFactorAuthenticationManager::class): static
    {
        $this->hasMultiFactorForm = $condition;
        $this->multiFactorManagerClass = $managerClass;

        return $this;
    }

    public function shouldShowUpdatePasswordForm(): bool
    {
        return $this->hasUpdatePasswordForm;
    }

    public function shouldShowMultiFactorForm(): bool
    {
        return $this->hasMultiFactorForm;
    }

    public function getMultiFactorManagerClass(): ?string
    {
        return $this->multiFactorManagerClass;
    }

    protected function getDefaultNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedShieldExclamation;
    }

    protected function getDefaultNavigationLabel(): ?string
    {
        return __('profile-filament::pages/security.title');
    }

    protected function getDefaultNavigationSort(): ?int
    {
        return 20;
    }

    protected function getDefaultTitle(): ?string
    {
        return __('profile-filament::pages/security.title');
    }
}
