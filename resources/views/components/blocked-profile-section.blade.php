@props([
    'title' => null,
])

<div class="py-8 flex justify-center items-center">
    <div class="text-center">
        <div class="flex justify-center">
            <x-filament::icon
                icon="heroicon-o-lock-closed"
                class="h-10 w-10 text-gray-500 dark:text-gray-400"
            />
        </div>

        <h2 class="mt-4 text-base text-gray-900 dark:text-gray-400">{{ $title ?? __('profile-filament::messages.blocked_profile_section.title') }}</h2>

        <p class="mt-2 text-sm text-gray-900 dark:text-gray-400">
            {{ $slot }}
        </p>
    </div>
</div>
