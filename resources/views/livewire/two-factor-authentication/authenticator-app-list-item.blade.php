<div class="py-3 flex justify-between items-center gap-x-3">
    @if ($app)
        <div>
            <div>
                <span>{{ $app->name }}</span>
                <span class="text-gray-500 dark:text-gray-400 text-xs">
                    {{ $app->registered_at }}
                </span>

                <x-filament-actions::modals />
            </div>

            <div>
                <span class="text-gray-500 dark:text-gray-400 text-xs">
                    {{ $app->last_used }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-x-2">
            @can('edit', $app)
                {{ $this->editAction }}
            @endcan

            {{ $this->deleteAction }}
        </div>
    @endif
</div>
