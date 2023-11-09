<div class="py-3 flex justify-between items-center gap-x-3">
    @if ($webauthnKey)
        <div>
            <div>
                <span>{{ $webauthnKey->name }}</span>
                <span class="text-gray-500 dark:text-gray-400 text-xs">
                    {{ $webauthnKey->registered_at }}
                </span>

                <x-filament-actions::modals />
            </div>

            <div>
                <span class="text-gray-500 dark:text-gray-400 text-xs">
                    {{ $webauthnKey->last_used }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-x-2">
            @can('upgradeToPasskey', $webauthnKey)
                {{ $this->upgradeAction }}
            @endcan

            @can('edit', $webauthnKey)
                {{ $this->editAction }}
            @endcan

            {{ $this->deleteAction }}
        </div>
    @endif
</div>
