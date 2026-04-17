@props([
    'heading' => null,
    'icon' => null,
    'alternatives' => null,
    'currentProvider' => null,
])

<x-profile-filament::plugin-css :attributes="$attributes->class(['pf-sudo-form space-y-3'])">
    <div @class([
        'px-4 py-3 border border-gray-300 dark:border-gray-500 rounded-md bg-gray-50 dark:bg-gray-800',
        'hidden' => blank($currentProvider),
    ])>
        {{-- heading --}}
        @if ($heading || $icon)
            <div class="gap-y-2 flex flex-col mb-4">
                @if ($icon)
                    <div class="flex justify-center">
                        <x-filament::icon
                            :icon="$icon"
                            class="pf-sudo-icon h-8 w-8 text-gray-600 dark:text-white"
                        />
                    </div>
                @endif

                @if ($heading)
                    <h3 class="pf-sudo-heading text-xl tracking-tight text-center text-gray-950 dark:text-white">
                        {{ $heading }}
                    </h3>
                @endif
            </div>
        @endif

        {{-- form --}}
        <div>
            {{ $slot }}
        </div>
    </div>

    @if ($alternatives)
        <div class="px-4 py-3 border border-gray-300 dark:border-gray-500 rounded-md">
            <p class="text-sm text-gray-950 dark:text-white">{{ __('profile-filament::auth/sudo/sudo.challenge.alternate-options') }}</p>

            <div class="mt-2">
                {{ $alternatives }}
            </div>
        </div>
    @endif
</x-profile-filament::plugin-css>
