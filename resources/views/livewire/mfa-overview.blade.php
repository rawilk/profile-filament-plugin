<div>
    <x-profile-filament::component-section>
        <x-slot:title>
            <span class="flex items-center gap-x-2">
                <span>{{ __('profile-filament::pages/security.mfa.title') }}</span>

                <x-filament::badge
                    :color="$this->hasMfaEnabled ? 'success' : 'danger'"
                >
                    {{ $this->hasMfaEnabled ? __('profile-filament::pages/security.mfa.status_enabled') : __('profile-filament::pages/security.mfa.status_disabled') }}
                </x-filament::badge>
            </span>
        </x-slot:title>

        <p class="text-sm">
            {{ __('profile-filament::pages/security.mfa.description') }}
        </p>

        {{ \Filament\Support\Facades\FilamentView::renderHook('profile-filament::mfa.settings.before') }}

        <div class="mt-6">
            @include('profile-filament::livewire.partials.mfa-summary')
        </div>
    </x-profile-filament::component-section>

    <x-filament-actions::modals />
</div>
