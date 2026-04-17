@props([
    'toggleLabel' => __('Toggle'),
])

@php
    $listId = str('multi-factor-list')->append(Str::random(8))->value();
@endphp

<div
    x-data="{ show: false }"
    {{ $attributes->class(['pf-multi-factor-list', 'flex space-x-2 -mt-3']) }}
>
    <div class="w-6"></div>

    <div class="w-full">
        <x-filament::link
            tag="button"
            color="gray"
            x-on:click="show = ! show"
            :icon-position="Filament\Support\Enums\IconPosition::After"
            :size="Filament\Support\Enums\Size::ExtraSmall"
            :aria-controls="$listId"
            x-bind:aria-expanded="show"
        >
            {{ $toggleLabel }}

            <x-slot:icon>
                <x-filament::icon :icon="Filament\Support\Icons\Heroicon::ChevronDown" x-show="! show" :size="Filament\Support\Enums\IconSize::Small" />
                <x-filament::icon :icon="Filament\Support\Icons\Heroicon::ChevronUp" x-show="show" :size="Filament\Support\Enums\IconSize::Small" />
            </x-slot:icon>
        </x-filament::link>

        <div
            x-show="show"
            class="mt-3 border-t border-gray-200 dark:border-gray-600 divide-y divide-gray-200 dark:divide-gray-600"
            id="{{ $listId }}"
        >
            {{ $slot }}
        </div>
    </div>
</div>
