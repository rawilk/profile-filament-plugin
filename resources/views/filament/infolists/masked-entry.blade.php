@php
    use Filament\Infolists\Components\TextEntry\TextEntrySize;
    use Filament\Support\Enums\FontFamily;
    use Filament\Support\Enums\FontWeight;
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $state = $getState();
        $color = $getColor($state);
        $fontFamily = $getFontFamily($state);
        $size = $getSize($state);
        $weight = $getWeight($state);
        $itemIsCopyable = $isCopyable($state);
        $copyMessage = $getCopyMessage($state);
        $copyMessageDuration = $getCopyMessageDuration($state);
    @endphp

    <div
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->class([
                    'fi-in-text',
                    'fi-mask',
                ])
        }}
    >
        <x-filament-infolists::affixes
            :prefix-actions="$getPrefixActions()"
            :suffix-actions="$getSuffixActions()"
        >
            <div>
                <div
                    @class([
                        'fi-in-text-item inline-flex items-center gap-1.5',
                        'fi-mask-text-item',
                        match ($color) {
                            null => 'text-gray-950 dark:text-white',
                            'gray' => 'fi-color-gray text-gray-500 dark:text-gray-400',
                            default => 'fi-color-custom text-custom-600 dark:text-custom-400',
                        },
                        match ($size) {
                            TextEntrySize::ExtraSmall, 'xs' => 'text-xs',
                            TextEntrySize::Small, 'sm', null => 'text-sm leading-6',
                            TextEntrySize::Medium, 'base', 'md' => 'text-base',
                            TextEntrySize::Large, 'lg' => 'text-lg',
                            default => $size,
                        },
                        match ($weight) {
                            FontWeight::Thin, 'thin' => 'font-thin',
                            FontWeight::ExtraLight, 'extralight' => 'font-extralight',
                            FontWeight::Light, 'light' => 'font-light',
                            FontWeight::Medium, 'medium' => 'font-medium',
                            FontWeight::SemiBold, 'semibold' => 'font-semibold',
                            FontWeight::Bold, 'bold' => 'font-bold',
                            FontWeight::ExtraBold, 'extrabold' => 'font-extrabold',
                            FontWeight::Black, 'black' => 'font-black',
                            default => $weight,
                        },
                        match ($fontFamily) {
                            FontFamily::Sans, 'sans' => 'font-sans',
                            FontFamily::Serif, 'serif' => 'font-serif',
                            FontFamily::Mono, 'mono' => 'font-mono',
                            default => $fontFamily,
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [400, 600],
                        ) => ! in_array($color, [null, 'gray'], true),
                    ])
                >
                    <livewire:masked-value
                        :masked-value="$maskedValue()"
                        :field="$getKey()"
                        :model="$getRecord()"
                        :requires-sudo="$isSudoRequired()"
                        :copyable="$itemIsCopyable"
                        :copy-message="$copyMessage"
                        :copy-message-duration="$copyMessageDuration"
                    />
                </div>
            </div>
        </x-filament-infolists::affixes>
    </div>
</x-dynamic-component>
