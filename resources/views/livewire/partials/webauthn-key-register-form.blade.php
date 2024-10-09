<x-profile-filament::webauthn-script
    mode="register"
    x-data="registerWebauthn({
        registerUrl: {{ Js::from(route('profile-filament::webauthn.attestation_pk')) }},
        before: function() {
            return $wire.validate()
                .then(() => ! this.hasErrors());
        },
    })"
    class="pr-2"
>
    @include('profile-filament::livewire.partials.webauthn-unsupported')

    <x-filament-panels::form
        wire:submit="verifyKey"
        :wire:key="$this->getId() . '.forms.data'"
        x-show="browserSupportsWebAuthn"
    >
        {{ $this->form }}

        <x-profile-filament::register-webauthn-errors>
            <x-slot:button>
                {{ $this->retryWebauthnAction }}
            </x-slot:button>
        </x-profile-filament::register-webauthn-errors>

        <x-profile-filament::webauthn-waiting-indicator
            x-show="processing"
            style="display: none;"
            x-cloak
            wire:ignore
            :message="__('profile-filament::pages/security.mfa.webauthn.actions.register.waiting')"
        />
    </x-filament-panels::form>
</x-profile-filament::webauthn-script>
