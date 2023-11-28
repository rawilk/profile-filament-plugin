@php
    $pluginId = \Rawilk\ProfileFilament\ProfileFilamentPlugin::PLUGIN_ID;

    /** @var \Rawilk\ProfileFilament\ProfileFilamentPlugin $plugin */
    $plugin = filament($pluginId);
@endphp

<x-filament-panels::page>
    <x-filament-inner-nav::page
        :inner-nav="$plugin->navigation()"
    >
        {{ $slot }}
    </x-filament-inner-nav::page>
</x-filament-panels::page>
