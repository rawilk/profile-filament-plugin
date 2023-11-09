@props([
    'width' => 5,
    'height' => 5,
    'color' => 'text-gray-500 dark:text-gray-400',
])

@php
    $widthClass = match ($width) {
        3 => 'w-3',
        4 => 'w-4',
        5 => 'w-5',
        6 => 'w-6',
        default => $width,
    };

    $heightClass = match ($height) {
        3 => 'h-3',
        4 => 'h-4',
        5 => 'h-5',
        6 => 'h-6',
        default => $height,
    };

    $classList = \Illuminate\Support\Arr::toCssClasses([
        $heightClass,
        $widthClass,
        $color,
        'fill-current',
    ]);
@endphp

<x-filament::icon
    alias="profile-filament::passkey"
    :class="$classList"
    icon="pf-passkey"
/>
