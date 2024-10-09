<div class="px-4 py-3 rounded-md border border-gray-300 dark:border-gray-600">
    <div class="flex gap-x-2 items-tart">
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
                {{
                    str(__('profile-filament::pages/settings.email.pending_description', [
                        'email' => e($pendingEmail?->email)
                    ]))
                        ->inlineMarkdown()
                        ->toHtmlString()
                }}
            </p>

            <div class="mt-3 flex items-center gap-x-2">
                {{ $resendAction }}
                <span class="inline-block rounded-full h-1 w-1 bg-gray-600" aria-hidden="true"></span>
                {{ $cancelAction }}
            </div>
        </div>
    </div>
</div>
