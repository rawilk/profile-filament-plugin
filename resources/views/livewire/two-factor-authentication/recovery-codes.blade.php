<div>
    <div id="recovery-codes-list"
         class="text-sm sm:text-base my-6 rounded-md border border-gray-300 dark:border-gray-500 text-gray-950 sm:w-3/4 dark:text-white pb-8"
    >
        <div class="px-4">
            <div class="pt-4 sm:pl-4">
                <h3 class="text-lg font-semibold">{{ __('profile-filament::pages/security.mfa.recovery_codes.current_codes_title') }}</h3>

                <div class="[&_a]:text-custom-600 [&_a]:fi-link [&_a:focus]:underline [&_a:hover]:underline dark:[&_a]:text-custom-400"
                     style="{{ Arr::toCssStyles([\Filament\Support\get_color_css_variables('primary', [300, 400, 500, 600])]) }}"
                >
                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        {{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::pages/security.mfa.recovery_codes.recommendation', ['1password' => 'https://1password.com/', 'authy' => 'https://authy.com/', 'keeper' => 'https://www.keepersecurity.com/'])) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <x-profile-filament::alert
                :color="$regenerated ? 'danger' : 'primary'"
                icon="heroicon-o-exclamation-triangle"
                alias="mfa::recovery-codes-notice"
                class="!rounded-none !border-x-0"
            >
                {{ \Rawilk\ProfileFilament\renderMarkdown(content: $regenerated ? __('profile-filament::pages/security.mfa.recovery_codes.regenerated_warning') : __('profile-filament::pages/security.mfa.recovery_codes.warning'), inline: false) }}
            </x-profile-filament::alert>
        </div>

        <div class="px-4">
            <ul class="grid sm:grid-cols-2 sm:gap-x-6 font-mono list-disc list-inside mt-4"
                role="list"
            >
                @foreach ($this->recoveryCodes as $code)
                    <li class="sm:text-center">{{ $code }}</li>
                @endforeach
            </ul>

            <div class="mt-6 sm:pl-4 flex gap-x-4">
                {{ $this->downloadAction }}
                {{ $this->printAction }}
                {{ $this->copyAction }}
            </div>
        </div>
    </div>

    {{-- regenerate codes --}}
    <div class="mt-2">
        <p class="text-sm font-bold">{{ __('profile-filament::pages/security.mfa.recovery_codes.actions.generate.heading') }}</p>
        <p class="text-xs mt-1 text-gray-500 dark:text-gray-300">
            {{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::pages/security.mfa.recovery_codes.actions.generate.description')) }}
        </p>

        <div class="mt-4">
            {{ $this->generateAction }}
        </div>
    </div>

    <x-filament-actions::modals />
</div>
