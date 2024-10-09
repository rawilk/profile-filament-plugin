@php
    use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
@endphp

<div id="mfa-summary" class="border rounded-md dark:border-gray-700 divide-y dark:divide-gray-700">
    <x-profile-filament::box-header>
        {{ __('profile-filament::pages/security.mfa.methods_title') }}
    </x-profile-filament::box-header>

    @if ($this->canUseAuthenticatorApps)
        <x-profile-filament::box-row
            icon="heroicon-o-device-phone-mobile"
            icon-alias="mfa::totp"
            id="totp-list-container"
            device-count-translation="profile-filament::pages/security.mfa.app.device_count"
            :label="__('profile-filament::pages/security.mfa.app.title')"
            :description="__('profile-filament::pages/security.mfa.app.description')"
            :device-count="$this->authenticatorApps->count()"
        >
            <x-slot:button>
                {{ $this->toggleTotpAction }}
            </x-slot:button>

            @livewire(Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\AuthenticatorAppForm::class, [
                'show' => $showAuthenticatorAppForm,
                'authenticatorApps' => $this->authenticatorApps,
            ], key($this->getId() . 'totpManager'))
        </x-profile-filament::box-row>
    @endif

    @if ($this->canUseWebauthn)
        <x-profile-filament::box-row
            icon="heroicon-o-shield-exclamation"
            icon-alias="mfa::webauthn"
            id="webauthn-list-container"
            device-count-translation="profile-filament::pages/security.mfa.webauthn.device_count"
            :label="__('profile-filament::pages/security.mfa.webauthn.title')"
            :description="__('profile-filament::pages/security.mfa.webauthn.description')"
            :device-count="$this->webauthnKeys->count()"
        >
            <x-slot:button>
                {{ $this->toggleWebauthnAction }}
            </x-slot:button>

            @livewire(\Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\WebauthnKeys::class, [
                'webauthnKeys' => $this->webauthnKeys,
            ], key($this->getId() . 'webauthnKeyManager'))
        </x-profile-filament::box-row>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Rawilk\ProfileFilament\Enums\RenderHook::MfaMethodsAfter->value) }}

    <x-profile-filament::box-header>
        {{ __('profile-filament::pages/security.mfa.recovery_title') }}
    </x-profile-filament::box-header>

    <x-profile-filament::box-row
        icon="heroicon-o-key"
        icon-alias="mfa::recovery-codes"
        :label="__('profile-filament::pages/security.mfa.recovery_codes.title')"
        :description="__('profile-filament::pages/security.mfa.recovery_codes.description')"
        id="recovery-codes-container"
    >
        <x-slot:button>
            <span
                @unless ($this->hasMfaEnabled)
                    {{-- tooltips don't show up on disabled buttons, so we'll wrap it like this instead --}}
                    x-tooltip="{
                        content: @js(__('profile-filament::pages/security.mfa.recovery_codes.mfa_disabled')),
                        theme: $store.theme,
                        offset: [0, 20],
                    }"
                    class="cursor-not-allowed"
                @endunless
            >
                {{ $this->toggleRecoveryCodesAction }}
            </span>
        </x-slot:button>

        @if ($showRecoveryCodes)
            @livewire(\Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\RecoveryCodes::class, [
            ], key($this->getId() . 'recoveryCodes'))
        @endif
    </x-profile-filament::box-row>
</div>
