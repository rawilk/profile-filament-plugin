@props([
    'icon' => null,
    'deviceCount' => 0,
    'description' => '',
    'deviceCountTranslation' => '',
    'button' => '',
    'label' => '',
])

<div {{ $attributes->class('py-4 px-4 flex justify-between') }}>
    @if ($icon)
        <x-filament::icon
            class="w-6 h-6"
            :icon="$icon"
        />
    @endif

    <div class="w-full">
        <div class="flex w-full">
            <div class="flex flex-col ml-2 w-full">
                <div class="flex flex-wrap items-center">
                    <p class="mr-2 my-0 whitespace-nowrap text-sm font-semibold">{{ $label }}</p>
                    @if ($deviceCount > 0)
                        <div class="flex gap-x-1">
                            <x-filament::badge color="success">
                                {{ __('profile-filament::pages/security.mfa.method_configured') }}
                            </x-filament::badge>

                            <x-filament::badge color="gray">
                                {{ trans_choice($deviceCountTranslation, $deviceCount, ['count' => $deviceCount]) }}
                            </x-filament::badge>
                        </div>
                    @endif
                </div>

                @if ($description)
                    <div class="mt-1">
                        <p class="text-xs text-gray-500 dark:text-gray-300">
                            {{ $description }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="shrink-0">
                {{ $button }}
            </div>
        </div>

        @unless ($slot->isEmpty())
            <div class="w-full mt-1.5 ml-2">{{ $slot }}</div>
        @endunless
    </div>
</div>
