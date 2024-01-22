<div
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('profile-filament-plugin', package: \Rawilk\ProfileFilament\ProfileFilamentPlugin::PLUGIN_ID))]"
    data-css-before="filament"
    {{ $attributes }}
>
    {{ $slot }}
</div>
