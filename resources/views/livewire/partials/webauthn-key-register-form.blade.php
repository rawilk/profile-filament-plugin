<div
    x-ignore
    ax-load="visible"
    ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('webauthnForm', package: 'rawilk/profile-filament-plugin') }}"
    x-data="webauthnForm({
        mode: 'register',
        wireId: '{{ $this->getId() }}',
        registerPublicKeyUrl: '{{ route('profile-filament::webauthn.attestation_pk') }}',

        beforeRegister: instance => {
            return @this.validate()
                .then(() => ! instance.hasErrors(@this));
        },
    })"
>
    @include('profile-filament::livewire.partials.webauthn-unsupported')

    <x-filament-panels::form x-show="browserSupported" wire:submit="verifyKey">
        {{ $this->form }}

        <div class="flex flex-col items-center"
             x-show="error && ! processing"
             style="display: none;"
             x-cloak
             wire:ignore
        >
            <div class="flex items-center gap-x-2 text-danger-600 dark:text-danger-400">
                <div>
                    <x-filament::icon
                        alias="profile-filament::webauthn-error"
                        icon="heroicon-o-exclamation-triangle"
                        class="h-4 w-4"
                    />
                </div>

                <span class="text-sm">
                    {{ __('profile-filament::pages/security.mfa.webauthn.actions.register.register_fail') }}
                </span>
            </div>

            <div class="mt-3">
                <x-filament::button
                    color="gray"
                    x-on:click="submit"
                    size="sm"
                >
                    {{ __('profile-filament::pages/security.mfa.webauthn.actions.register.retry_button') }}
                </x-filament::button>
            </div>
        </div>

        <x-profile-filament::webauthn-waiting-indicator
            x-show="processing"
            style="display: none;"
            x-cloak
            wire:ignore
            :message="__('profile-filament::pages/security.mfa.webauthn.actions.register.waiting')"
        />
    </x-filament-panels::form>
</div>
