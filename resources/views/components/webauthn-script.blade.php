<div
    x-ignore
    ax-load="visible"
    ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('webauthnForm', package: \Rawilk\ProfileFilament\ProfileFilamentPlugin::PLUGIN_ID) }}"
    {{ $attributes }}
>
    {{ $slot }}
</div>
