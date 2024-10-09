@props([
    'mode' => 'authenticate',
])

@php
    $component = match ($mode) {
        'register' => 'registerWebauthn',
        default => 'authenticateWebauthn',
    };
@endphp

<div
    x-ignore
    ax-load="visible"
    ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc($component, package: \Rawilk\ProfileFilament\ProfileFilamentPlugin::PLUGIN_ID) }}"
    {{ $attributes }}
>
    {{ $slot }}
</div>
