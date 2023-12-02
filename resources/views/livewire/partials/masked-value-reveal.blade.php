<x-profile-filament::plugin-css>
    <div class="blur-[3px] hover:shadow-md cursor-pointer relative overflow-hidden"
         wire:click="{{ $action->getLivewireClickHandler() }}"
         x-on:keydown.enter="$wire.{{ $action->getLivewireClickHandler() }}"
         x-on:keydown.space="$wire.{{ $action->getLivewireClickHandler() }}"
         role="button"
         tabindex="0"
         x-data
         x-tooltip="{
            content: @js(__('profile-filament::messages.masked_value.reveal_button')),
            theme: $store.theme,
         }"
    >
        <span class="sr-only">{{ __('profile-filament::messages.masked_value.reveal_button') }}</span>

        <div aria-hidden="true" class="w-full break-all overflow-hidden">{{ $value }}</div>
    </div>
</x-profile-filament::plugin-css>
