<div>
    <x-profile-filament::component-section
        :title="__('profile-filament::pages/security.password.title')"
    >
        @if ($this->shouldShowForm)
            <form wire:submit="updatePassword">
                <div>
                    {{ $this->form }}
                </div>

                <div class="mt-6 flex items-center gap-x-4">
                    <div class="w-auto">
                        <x-filament::button type="submit" form="updatePassword" class="w-full">
                            {{ __('profile-filament::pages/security.password.form.save_button') }}
                        </x-filament::button>
                    </div>

                    @if ($passwordResetUrl = filament()->getRequestPasswordResetUrl())
                        <div>
                            <x-filament::link :href="$passwordResetUrl">
                                {{ __('profile-filament::pages/security.password.form.forgot_password_link') }}
                            </x-filament::link>
                        </div>
                    @endif
                </div>
            </form>
        @else
            <x-profile-filament::blocked-profile-section>
                {{ __('profile-filament::messages.blocked_profile_section.update_password') }}
            </x-profile-filament::blocked-profile-section>
        @endif
    </x-profile-filament::component-section>
</div>
