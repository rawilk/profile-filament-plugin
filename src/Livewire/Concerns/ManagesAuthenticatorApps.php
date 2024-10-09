<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Concerns;

use Filament\Actions\Action;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\ToggleTotpFormAction;

/**
 * @mixin \Rawilk\ProfileFilament\Livewire\MfaOverview
 *
 * @property-read bool $canUseAuthenticatorApps
 * @property-read Collection<int, \Rawilk\ProfileFilament\Models\AuthenticatorApp> $authenticatorApps
 */
trait ManagesAuthenticatorApps
{
    #[Locked]
    public bool $showAuthenticatorAppForm = false;

    #[Computed]
    public function canUseAuthenticatorApps(): bool
    {
        return $this->panelFeatures->hasAuthenticatorApps();
    }

    #[Computed]
    public function authenticatorApps(): Collection
    {
        if (! $this->canUseAuthenticatorApps) {
            return collect();
        }

        return $this->user->authenticatorApps ?? collect();
    }

    public function toggleTotpAction(): Action
    {
        return ToggleTotpFormAction::make()
            ->label(function (): string {
                if (! $this->showAuthenticatorAppForm && $this->authenticatorApps->isEmpty()) {
                    return __('profile-filament::pages/security.mfa.app.add_button');
                }

                return $this->showAuthenticatorAppForm
                    ? __('profile-filament::pages/security.mfa.app.hide_button')
                    : __('profile-filament::pages/security.mfa.app.show_button');
            })
            ->disabled(function (): bool {
                if ($this->authenticatorApps->isNotEmpty()) {
                    return false;
                }

                return $this->showAuthenticatorAppForm;
            })
            ->beforeSudoModeCheck(function (): bool {
                // Don't bother checking/prompting for sudo mode if these conditions are not met.
                if ($this->showAuthenticatorAppForm) {
                    return false;
                }

                return $this->authenticatorApps->isEmpty();
            })
            ->action(function () {
                if ($this->showAuthenticatorAppForm) {
                    $this->showAuthenticatorAppForm = false;
                    $this->dispatch(MfaEvent::HideAppList->value);
                } else {
                    $this->showAuthenticatorAppForm = true;
                    $this->dispatch(MfaEvent::ShowAppForm->value);
                }
            });
    }
}
