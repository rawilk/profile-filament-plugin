@props([
    'color' => 'danger',
    'icon' => null,
    'iconAlias' => null,
    'dismiss' => false,
])

@php
    $styles = \Illuminate\Support\Arr::toCssStyles([
        \Filament\Support\get_color_css_variables($color, [50, 100, 300, 400, 500, 600, 700])
    ]);

    $classes = \Illuminate\Support\Arr::toCssClasses([
        'fi-profile-alert',
        'rounded-md',
        'px-4 py-6',
        'bg-custom-50 dark:bg-custom-500/10',
        'border border-custom-300 dark:border-custom-400',
    ]);
@endphp

<div {{ $attributes->class($classes)->style($styles)->merge(['role' => 'alert']) }}
     @if ($dismiss)
         x-data="{
            dismiss() {
                $root.remove();
            },
         }"
     @endif
>
    <x-profile-filament::plugin-css class="flex">
        <div @class(['flex', 'flex-1' => $dismiss])>
            @if ($icon)
                <div class="flex-shrink-0">
                    <x-filament::icon
                        :icon="$icon"
                        :icon-alias="$iconAlias"
                        class="h-5 w-5 text-custom-400"
                    />
                </div>
            @endif

            <div @class(['ml-3' => $icon])>
                <div class="text-sm text-custom-700 dark:text-white dark:font-semibold">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @if ($dismiss)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button
                        type="button"
                        class="inline-flex rounded-md bg-custom-50 dark:bg-custom-500/10 p-1.5 text-custom-500 hover:bg-custom-100 dark:hover:bg-custom-700/10 focus:outline-none focus:ring-2 focus:ring-custom-600 focus:ring-offset-2 focus:ring-offset-custom-50 dark:focus:ring-offset-custom-700"
                        x-on:click="dismiss"
                    >
                        <span class="sr-only">{{ __('profile-filament::messages.alert.dismiss') }}</span>

                        <x-filament::icon
                            alias="profile-filament::alert-dismiss"
                            icon="heroicon-m-x-mark"
                            class="h-5 w-5"
                        />
                    </button>
                </div>
            </div>
        @endif
    </x-profile-filament::plugin-css>
</div>
