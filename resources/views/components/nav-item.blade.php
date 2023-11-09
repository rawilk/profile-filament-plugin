@props([
    'href' => '#',
    'active' => null,
    'color' => null,
    'icon' => null,
    'iconAlias' => null,
])

@php
    $active = is_bool($active)
        ? $active
        : request()->fullUrlIs($href);

    $color ??= config('profile-filament.component_defaults.nav_item.color');

    $linkClasses = \Illuminate\Support\Arr::toCssClasses([
        'profile-nav-link',
        'relative',
        'group flex gap-x-3 rounded-md py-2 pl-2 pr-3 text-sm leading-6 font-semibold',
        'bg-gray-100 text-custom-600 dark:bg-gray-800 dark:text-custom-400' => $active,
        'text-gray-700 hover:text-custom-600 hover:bg-gray-100 focus:bg-gray-100 dark:text-gray-200 dark:hover:text-custom-400 dark:hover:bg-white/5 dark:focus:bg-white/5' => ! $active,
    ]);

    $linkStyles = \Illuminate\Support\Arr::toCssStyles([
        \Filament\Support\get_color_css_variables($color, shades: [400, 600]) => $color !== 'gray',
    ]);

    $iconClasses = \Illuminate\Support\Arr::toCssClasses([
        'profile-nav-link__icon',
        'w-6 h-6 shrink-0',
        'text-custom-600 dark:text-custom-200' => $active,
        'text-gray-400 group-hover:text-custom-600 dark:group-hover:text-custom-400' => ! $active,
    ]);
@endphp

<li>
    <a href="{{ $href }}"
       {{ $attributes->class($linkClasses)->style($linkStyles) }}
    >
        @if ($icon)
            <x-filament::icon
                :icon="$icon"
                :alias="$iconAlias"
                :class="$iconClasses"
            />
        @endif

        <span class="truncate w-full hover:text-clip hover:whitespace-normal">
            {{ $slot }}
        </span>
    </a>
</li>
