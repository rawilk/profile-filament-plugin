<div class="rounded-md border dark:border-gray-500 py-6 px-8" id="{{ $this->getId() }}.no-passkeys">
    <div class="flex flex-col items-center justify-center w-full text-center py-4">
        <div>
            <x-profile-filament::icons.passkey
                :width="6"
                :height="6"
            />
        </div>

        <h3 class="mt-3 text-lg font-semibold tracking-tight">{{ __('profile-filament::pages/security.passkeys.empty_heading') }}</h3>
        <div class="text-sm mt-2 space-y-2 sm:max-w-xl text-balance">
            {{ str(__('profile-filament::pages/security.passkeys.empty_description'))->markdown()->toHtmlString() }}
        </div>

        <div class="mt-6">
            {{ $this->addAction }}
        </div>
    </div>
</div>
