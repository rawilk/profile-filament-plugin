<div>
    <x-profile-filament::component-section
        :title="__('profile-filament::pages/security.passkeys.title')"
    >
        @if ($this->shouldShow)
            @includeWhen($passkeys->isEmpty(), 'profile-filament::livewire.partials.no-passkeys')

            @includeWhen($passkeys->isNotEmpty(), 'profile-filament::livewire.partials.passkey-list')

            <x-filament-actions::modals />
        @else
            <x-profile-filament::blocked-profile-section>
                {{ __('profile-filament::messages.blocked_profile_section.passkeys') }}
            </x-profile-filament::blocked-profile-section>
        @endif
    </x-profile-filament::component-section>
</div>
