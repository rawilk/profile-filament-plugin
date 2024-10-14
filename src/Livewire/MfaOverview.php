<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Features;

/**
 * @property-read bool $hasMfaEnabled
 * @property-read Features $panelFeatures
 * @property-read User $user
 */
class MfaOverview extends ProfileComponent
{
    use Concerns\CopiesRecoveryCodes;
    use Concerns\ManagesAuthenticatorApps;
    use Concerns\ManagesRecoveryCodes;
    use Concerns\ManagesWebauthn;
    use UsesSudoChallengeAction;

    #[Computed]
    public function hasMfaEnabled(): bool
    {
        return Mfa::userHasMfaEnabled($this->user);
    }

    #[Computed]
    public function panelFeatures(): Features
    {
        return $this->profilePlugin->panelFeatures();
    }

    #[Computed]
    public function user(): User
    {
        return filament()->auth()->user();
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <x-filament::section>
                <x-slot:heading>
                    <div class="flex items-center gap-x-2">
                        <span>{{ __('profile-filament::pages/security.mfa.title') }}</span>

                        <x-filament::badge
                            :color="$this->hasMfaEnabled ? 'success' : 'danger'"
                        >
                            {{ $this->hasMfaEnabled ? __('profile-filament::pages/security.mfa.status_enabled') : __('profile-filament::pages/security.mfa.status_disabled') }}
                        </x-filament::badge>
                    </div>
                </x-slot:heading>

                <p class="text-sm text-pretty">
                    {{ __('profile-filament::pages/security.mfa.description') }}
                </p>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Rawilk\ProfileFilament\Enums\RenderHook::MfaSettingsBefore->value) }}

                <div class="mt-6">
                    @include('profile-filament::livewire.partials.mfa-summary')
                </div>
            </x-filament::section>

            @include('profile-filament::livewire.partials.recovery-codes-modal')

            <x-filament-actions::modals />
        </div>
        HTML;
    }

    #[On(MfaEvent::AppAdded->value)]
    #[On(MfaEvent::WebauthnKeyAdded->value)]
    #[On(MfaEvent::PasskeyRegistered->value)]
    #[On('sudo-active')]
    public function onMfaMethodAdded(bool $enabledMfa): void
    {
        if ($enabledMfa && $this->ensureSudoIsActive()) {
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
}
