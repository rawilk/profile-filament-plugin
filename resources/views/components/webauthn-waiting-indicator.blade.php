@php
    use Filament\Support\Enums\IconSize;
@endphp

@props([
    'iconSize' => IconSize::Medium,
    'iconClass' => null,
    'iconColor' => 'gray',
    'message' => '',
])

@php
    $iconClasses = \Illuminate\Support\Arr::toCssClasses([
        'fi-webauthn-waiting-icon',
        match ($iconSize) {
            IconSize::Small, 'sm' => 'h-4 w-4',
            IconSize::Medium, 'md' => 'h-5 w-5',
            IconSize::Large, 'lg' => 'h-6 w-6',
            default => $iconSize,
        },
        match ($iconColor) {
            'gray' => 'text-gray-400 dark:text-gray-500',
            default => null,
        },
        $iconClass,
    ]);
@endphp

<div {{ $attributes->class('flex items-center justify-center w-full gap-x-2') }}>
    <div>
        <x-filament::loading-indicator
            :class="$iconClasses"
        />
    </div>

    <div>{{ $message }}</div>
</div>
