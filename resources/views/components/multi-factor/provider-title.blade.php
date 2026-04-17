@props([
    'icon' => null,
    'iconAlias' => null,
    'description' => null,
    'isEnabled' => false,
    'configuredLabel' => null,
    'badges' => null,
    'label',
])

<div {{ $attributes->class('pf-mfa-provider-title flex space-x-2') }}>
    @if ($icon)
        <x-filament::icon
            class="w-6 h-6"
            :icon="$icon"
            :alias="$iconAlias"
        />
    @endif

    <div class="w-full">
        <div class="flex w-full">
            <div class="flex flex-col w-full">
                <div class="flex flex-wrap items-center">
                    <p class="mr-2 my-0 whitespace-nowrap text-sm font-semibold">{{ $label }}</p>

                    @if ($isEnabled && filled($configuredLabel))
                        <div @class([
                            'flex',
                            'mr-1' => filled($badges),
                        ])>
                            <x-filament::badge color="success">{{ $configuredLabel }}</x-filament::badge>
                        </div>
                    @endif

                    @if ($badges)
                        <div class="flex gap-x-1">
                            {{ $badges }}
                        </div>
                    @endif
                </div>

                @if ($description)
                    <div class="mt-1">
                        <div class="text-sm text-gray-500 dark:text-gray-300 text-pretty">
                            {{ $description }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
