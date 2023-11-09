<div>
    @if ($passkey)
        <div class="flex justify-between gap-x-3">
            <div class="flex-1 flex items-start">
                <div class="text-gray-500 dark:text-white">
                    <x-filament::icon
                        class="w-6 h-6"
                        icon="pf-passkey"
                        alias="profile-filament::passkey-item-icon"
                    />
                </div>

                <div class="ml-4">
                    <div>
                        <span class="text-sm font-semibold">{{ $passkey->name }}</span>
                        <span class="text-gray-500 dark:text-gray-400 text-xs">
                            {{ $passkey->registered_at }}
                        </span>

                        <x-filament-actions::modals />
                    </div>

                    <div>
                        <span class="text-gray-500 dark:text-gray-400 text-xs">
                            {{ $passkey->last_used }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-x-2 shrink-0">
                @can('edit', $passkey)
                    {{ $this->editAction }}
                @endcan

                {{ $this->deleteAction }}
            </div>
        </div>
    @endif
</div>
