@php
    $cssSrc = \Filament\Support\Facades\FilamentAsset::getStyleHref('profile-filament-plugin', package: \Rawilk\ProfileFilament\ProfileFilamentPlugin::PLUGIN_ID);
@endphp

<div
    data-dispatch="pf-loaded"
    x-data
    x-load-css="[@js($cssSrc)]"
    x-on:pf-loaded-css.window.once="() => {
        if (window.__pfStylesLoaded === true) { return }
        const style = document.head.querySelector('link[href=\'{{ $cssSrc }}\']');
        style && style.remove();
        style && document.head.prepend(style);
        window.__pfStylesLoaded = true;
    }"
    {{ $attributes }}
>
    {{ $slot }}
</div>
