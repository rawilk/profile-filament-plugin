<div>
    <x-profile-filament::component-section
        :title="__('profile-filament::pages/security.passkeys.title')"
    >
        @includeWhen($passkeys->isEmpty(), 'profile-filament::livewire.partials.no-passkeys')

        @includeWhen($passkeys->isNotEmpty(), 'profile-filament::livewire.partials.passkey-list')

        <x-filament-actions::modals />
    </x-profile-filament::component-section>
</div>
