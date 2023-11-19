<div>
    <x-profile-filament::component-section
        :title="__('profile-filament::pages/security.password.title')"
    >
        <x-filament-panels::form wire:submit="updatePassword">
            <div>
                {{ $this->form }}
            </div>

            <div class="flex items-center gap-x-4">
                <div class="w-auto">
                    {{ $this->submitAction }}
                </div>

                @if ($this->passwordResetUrl)
                    <div>
                        <x-filament::link :href="$this->passwordResetUrl">
                            {{ __('profile-filament::pages/security.password.form.forgot_password_link') }}
                        </x-filament::link>
                    </div>
                @endif
            </div>
        </x-filament-panels::form>

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
    </x-profile-filament::component-section>
</div>
