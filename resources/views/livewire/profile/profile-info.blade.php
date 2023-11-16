<div>
    <x-profile-filament::component-section
        :title="__('profile-filament::pages/profile.info.heading')"
    >
        <x-slot:actions>
            {{ $this->editAction }}
        </x-slot:actions>

        {{ $this->infoList }}
    </x-profile-filament::component-section>

    <x-filament-actions::modals />
</div>
