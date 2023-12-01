<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Action;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Rawilk\ProfileFilament\Concerns\CopiesRecoveryCodes;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\RecoveryCodesViewed;
use Rawilk\ProfileFilament\Features;

/**
 * @property-read \Illuminate\Support\Collection<int, \Rawilk\ProfileFilament\Models\AuthenticatorApp> $authenticatorApps
 * @property-read bool $canAuthenticatorApps
 * @property-read bool $canWebauthn
 * @property-read bool $hasMfaEnabled
 * @property-read \Rawilk\ProfileFilament\Features $panelFeatures
 * @property-read \Illuminate\Support\Collection<int, \Rawilk\ProfileFilament\Models\WebauthnKey> $webauthnKeys
 */
class MfaOverview extends ProfileComponent
{
    use CopiesRecoveryCodes;
    use UsesSudoChallengeAction;

    #[Locked]
    public bool $showAuthenticatorAppForm = false;

    #[Locked]
    public bool $showWebauthn = false;

    public bool $showRecoveryCodes = false;

    #[Locked]
    public bool $showRecoveryInModal = false;

    #[Computed]
    public function hasMfaEnabled(): bool
    {
        /** @phpstan-ignore-next-line */
        return filament()->auth()->user()->two_factor_enabled;
    }

    #[Computed]
    public function canAuthenticatorApps(): bool
    {
        return $this->panelFeatures->hasAuthenticatorApps();
    }

    #[Computed]
    public function canWebauthn(): bool
    {
        return $this->panelFeatures->hasWebauthn();
    }

    #[Computed]
    public function authenticatorApps(): Collection
    {
        if (! $this->canAuthenticatorApps) {
            return collect();
        }

        return filament()
            ->auth()
            ->user()
            ->authenticatorApps ?? collect();
    }

    #[Computed]
    public function webauthnKeys(): Collection
    {
        if (! $this->canWebauthn) {
            return collect();
        }

        return filament()
            ->auth()
            ->user()
            ->nonPasskeyWebauthnKeys ?? collect();
    }

    #[Computed]
    public function panelFeatures(): Features
    {
        return $this->profilePlugin->panelFeatures();
    }

    #[On(MfaEvent::AppAdded->value)]
    #[On(MfaEvent::WebauthnKeyAdded->value)]
    #[On(MfaEvent::PasskeyRegistered->value)]
    public function onMfaMethodAdded(bool $enabledMfa): void
    {
        if ($enabledMfa) {
            $this->showRecoveryInModal = true;
            $this->dispatch('open-modal', id: 'mfa-recovery-codes');
        }
    }

    #[On(MfaEvent::HideAppForm->value)]
    public function hideAuthenticatorAppForm(): void
    {
        $this->showAuthenticatorAppForm = false;
    }

    #[On(MfaEvent::AppDeleted->value)]
    #[On(MfaEvent::WebauthnKeyDeleted->value)]
    #[On(MfaEvent::WebauthnKeyUpgradedToPasskey->value)]
    public function refreshStatuses(): void
    {
        unset($this->hasMfaEnabled, $this->authenticatorApps, $this->webauthnKeys);

        if ($this->authenticatorApps->isEmpty()) {
            $this->showAuthenticatorAppForm = false;
        }

        if ($this->webauthnKeys->isEmpty()) {
            $this->showWebauthn = false;
        }

        if (! $this->hasMfaEnabled) {
            $this->showRecoveryInModal = false;
            $this->showRecoveryCodes = false;
            $this->showAuthenticatorAppForm = false;
            $this->showWebauthn = false;
        }
    }

    public function toggleTotpAction(): Action
    {
        return Action::make('toggleTotp')
            ->label(function () {
                if (! $this->showAuthenticatorAppForm && $this->authenticatorApps->isEmpty()) {
                    return __('profile-filament::pages/security.mfa.app.add_button');
                }

                return $this->showAuthenticatorAppForm
                    ? __('profile-filament::pages/security.mfa.app.hide_button')
                    : __('profile-filament::pages/security.mfa.app.show_button');
            })
            ->color('gray')
            ->size('sm')
            ->disabled(function () {
                if ($this->authenticatorApps->isNotEmpty()) {
                    return false;
                }

                return $this->showAuthenticatorAppForm;
            })
            ->action(function () {
                if ($this->showAuthenticatorAppForm) {
                    $this->showAuthenticatorAppForm = false;
                    $this->dispatch(MfaEvent::HideAppList->value);
                } else {
                    $this->showAuthenticatorAppForm = true;
                    $this->dispatch(MfaEvent::ShowAppForm->value);
                }
            })
            ->mountUsing(function () {
                if (! $this->showAuthenticatorAppForm && $this->authenticatorApps->isEmpty()) {
                    $this->ensureSudoIsActive(returnAction: 'toggleTotp');
                }
            });
    }

    public function toggleWebauthnAction(): Action
    {
        return Action::make('toggleWebauthn')
            ->label(function () {
                if (! $this->showWebauthn && $this->webauthnKeys->isEmpty()) {
                    return __('profile-filament::pages/security.mfa.webauthn.add_button');
                }

                return $this->showWebauthn
                    ? __('profile-filament::pages/security.mfa.webauthn.hide_button')
                    : __('profile-filament::pages/security.mfa.webauthn.show_button');
            })
            ->color('gray')
            ->size('sm')
            ->action(function () {
                $this->showWebauthn = ! $this->showWebauthn;

                $this->dispatch(MfaEvent::ToggleWebauthnKeys->value, show: $this->showWebauthn);
            })
            ->mountUsing(function () {
                if (! $this->showWebauthn && $this->webauthnKeys->isEmpty()) {
                    $this->ensureSudoIsActive(returnAction: 'toggleWebauthn');
                }
            });
    }

    public function toggleRecoveryCodesAction(): Action
    {
        return Action::make('toggleRecoveryCodes')
            ->label(fn () => $this->showRecoveryCodes ? __('profile-filament::pages/security.mfa.recovery_codes.hide_button') : __('profile-filament::pages/security.mfa.recovery_codes.show_button'))
            ->color('gray')
            ->disabled(fn () => ! $this->hasMfaEnabled)
            ->tooltip(fn () => $this->hasMfaEnabled ? null : __('profile-filament::pages/security.mfa.recovery_codes.mfa_disabled'))
            ->size('sm')
            ->action(function () {
                $this->showRecoveryCodes = ! $this->showRecoveryCodes;

                if ($this->showRecoveryCodes) {
                    RecoveryCodesViewed::dispatch(filament()->auth()->user());
                }
            })
            ->mountUsing(function () {
                // Hiding the recovery codes doesn't need re-authentication.
                if ($this->showRecoveryCodes) {
                    return;
                }

                $this->ensureSudoIsActive(returnAction: 'toggleRecoveryCodes');
            });
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.mfa-overview';
    }
}
