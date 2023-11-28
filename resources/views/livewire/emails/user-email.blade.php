<div>
    <x-profile-filament::component-section>
        <x-slot:title>
            <span class="flex items-center gap-x-2" id="current-email-heading">
                <span>{{ __('profile-filament::pages/settings.email.heading') }}</span>

                @if ($pendingEmail)
                    <x-filament::badge color="warning">
                        {{ __('profile-filament::pages/settings.email.change_pending_badge') }}
                    </x-filament::badge>
                @endif
            </span>
        </x-slot:title>

        <div>
            @if ($pendingEmail)
                <div class="mb-4 px-4 py-3 rounded-md border border-gray-300 dark:border-gray-600">
                    <div class="flex gap-x-2 items-start">
                        <div class="shrink-0">
                            <x-filament::icon
                                alias="profile-filament::pending-email-info"
                                icon="heroicon-o-information-circle"
                                class="h-5 w-5 text-primary-500 dark:text-primary-400"
                            />
                        </div>

                        <div class="flex-1">
                            <div class="text-sm font-bold">{{ __('profile-filament::pages/settings.email.pending_heading') }}</div>

                            <p class="mt-1 text-sm">
                                {{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::pages/settings.email.pending_description', ['email' => e($pendingEmail->email)])) }}
                            </p>

                            <div class="mt-3 flex items-center gap-x-2">
                                {{ $this->resendAction }}
                                <span class="inline-block rounded-full h-1 w-1 bg-gray-600" aria-hidden="true"></span>
                                {{ $this->cancelAction }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="px-4 py-3 rounded-md bg-gray-200 dark:bg-gray-600" aria-labelledby="current-email-heading" aria-describedby="current-email-description">
            {{ $this->user->email }}
        </div>

        <p class="text-sm mt-2" id="current-email-description">{{ __('profile-filament::pages/settings.email.email_description') }}</p>

        @unless ($pendingEmail)
            <div class="mt-4">
                {{ $this->editAction }}
            </div>
        @endunless

        @if ($this->securityUrl)
            <div class="mt-4 text-xs gap-x-1 flex items-center [&_a]:text-primary-600 dark:[&_a]:text-primary-400 [&_a:hover]:underline">
                <div>
                    <x-filament::icon
                        alias="profile-filament::help"
                        icon="heroicon-o-question-mark-circle"
                        class="h-5 w-5"
                    />
                </div>

                <span>{{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::pages/settings.account_security_link', ['url' => $this->securityUrl])) }}</span>
            </div>
        @endif

        <x-filament-actions::modals />
    </x-profile-filament::component-section>
</div>
