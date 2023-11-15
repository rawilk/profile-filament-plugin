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

            <div class="mt-4 text-xs gap-x-1 flex items-center">
                <div>
                    <x-filament::icon
                        alias="profile-filament::help"
                        icon="heroicon-o-question-mark-circle"
                        class="h-5 w-5"
                    />
                </div>

                <span>{{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::pages/security.password.form.form_info')) }}</span>
            </div>
        @else
            <x-profile-filament::blocked-profile-section>
                {{ __('profile-filament::messages.blocked_profile_section.update_password') }}
            </x-profile-filament::blocked-profile-section>
        @endif
    </x-profile-filament::component-section>
</div>
