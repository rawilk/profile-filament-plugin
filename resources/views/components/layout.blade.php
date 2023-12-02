@php
    $pluginId = \Rawilk\ProfileFilament\ProfileFilamentPlugin::PLUGIN_ID;

    /** @var \Rawilk\ProfileFilament\ProfileFilamentPlugin $plugin */
    $plugin = filament($pluginId);
@endphp

<x-filament-panels::page>
    <x-profile-filament::plugin-css>
        <x-filament-inner-nav::page
            :inner-nav="$plugin->navigation()"
        >
            {{ $slot }}
        </x-filament-inner-nav::page>
    </x-profile-filament::plugin-css>
</x-filament-panels::page>
