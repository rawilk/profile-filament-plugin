<x-filament::modal
    id="mfa-recovery-codes"
    width="3xl"
    :heading="__('profile-filament::pages/security.mfa.recovery_codes.current_codes_title')"
    :description="__('profile-filament::pages/security.mfa.recovery_codes.description')"
    icon="heroicon-o-key"
    icon-alias="mfa::recovery-codes"
    :close-by-clicking-away="false"
>
    @if ($showRecoveryInModal && filament()->auth()->user()->two_factor_enabled)
        <x-profile-filament::plugin-css class="rounded-md border border-gray-300 dark:border-gray-500 pb-8">
            <div class="px-4">
                <div class="pt-4 sm:pl-4">
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
                    color="primary"
                    icon="heroicon-o-exclamation-triangle"
                    icon-alias="mfa::recovery-codes-notice"
                    class="rounded-none border-x-0"
                >
                    {{ \Rawilk\ProfileFilament\renderMarkdown(__('profile-filament::pages/security.mfa.recovery_codes.warning')) }}
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
        </x-profile-filament::plugin-css>
    @endif
</x-filament::modal>
