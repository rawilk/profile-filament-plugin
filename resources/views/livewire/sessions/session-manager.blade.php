<div>
    <x-profile-filament::component-section
        :title="__('profile-filament::pages/sessions.manager.heading')"
    >
        <p class="text-sm">{{ __('profile-filament::pages/sessions.manager.description') }}</p>

        <div class="mt-4">
            {{ $this->revokeAllAction }}
        </div>

        <div>
            @includeWhen($this->sessions->isNotEmpty(), 'profile-filament::livewire.sessions.session-list')
        </div>
    </x-profile-filament::component-section>

    <x-filament-actions::modals />
</div>
