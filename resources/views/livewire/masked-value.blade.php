<div>
    @if ($reveal)
        <div
            @if ($copyable)
                x-data
                x-on:click="
                    window.navigator.clipboard.writeText(@js($model->{$field}));
                    $tooltip(@js($copyMessage ?? __('filament::components/copyable.messages.copied')), {
                        theme: $store.theme,
                        timeout: @js($copyMessageDuration),
                    });
                "
                class="cursor-pointer max-w-max"
            @endif
        >
            {{ $model->{$field} }}
        </div>
    @else
        <div>
            {{ $this->revealAction }}
        </div>

        <x-filament-actions::modals />
    @endif
</div>
