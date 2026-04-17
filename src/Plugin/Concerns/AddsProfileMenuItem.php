<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Contracts\Support\Htmlable;

trait AddsProfileMenuItem
{
    protected bool $showInUserMenu = true;

    protected Closure|Htmlable|null|string $profileMenuLabel = null;

    protected Closure|BackedEnum|null|string $profileMenuIcon = null;

    protected ?Closure $configureProfileMenuItemCallback = null;

    public function addProfileMenuItem(bool $condition = true): static
    {
        $this->showInUserMenu = $condition;

        return $this;
    }

    public function useProfileMenuLabel(Closure|Htmlable|null|string $label = null): static
    {
        $this->profileMenuLabel = $label;

        return $this;
    }

    public function useProfileMenuIcon(Closure|BackedEnum|null|string $icon = null): static
    {
        $this->profileMenuIcon = $icon;

        return $this;
    }

    public function configureProfileMenuItemAction(?Closure $callback = null): static
    {
        $this->configureProfileMenuItemCallback = $callback;

        return $this;
    }

    public function hideFromUserMenu(): static
    {
        $this->addProfileMenuItem(false);

        return $this;
    }

    protected function configureProfileMenuItem(Panel $panel): void
    {
        if (! $this->showInUserMenu) {
            return;
        }

        $panel->userMenuItems([
            'profile' => function (Action $action) {
                $action
                    ->label($this->profileMenuLabel ?? Filament::getUserName(Filament::auth()->user()))
                    ->icon($this->profileMenuIcon ?? FilamentIcon::resolve(PanelsIconAlias::USER_MENU_PROFILE_ITEM) ?? Heroicon::UserCircle)
                    ->url($this->getDefaultProfilePageUrl());

                if ($this->configureProfileMenuItemCallback) {
                    ($this->configureProfileMenuItemCallback)($action);
                }
            },
        ]);
    }
}
