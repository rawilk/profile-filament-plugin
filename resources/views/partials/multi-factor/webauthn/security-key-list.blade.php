<x-profile-filament::multi-factor.multi-factor-list
    :toggle-label="Lang::choice('profile-filament::auth/multi-factor/webauthn/provider.management-schema.list.toggle-list', $securityKeys->count(), ['count' => $securityKeys->count()])"
>
    @foreach ($securityKeys as $securityKey)
        <div class="py-3 last:pb-0 flex items-center justify-between">
            <div class="flex-auto min-w-0 pr-2">
                <div class="font-semibold text-sm">{{ $securityKey->name }}</div>

                <div class="text-xs mt-1 text-neutral-600 dark:text-neutral-300">
                    <span>{{ $securityKey->registered_at }}</span>
                    <span aria-hidden="true">|</span>
                    <span>{{ $securityKey->last_used }}</span>
                </div>

                @unless ($securityKey->is_passkey)
                    <div class="text-xs text-neutral-500 dark:text-neutral-300 italic">
                        {{ __('profile-filament::auth/multi-factor/webauthn/provider.management-schema.messages.not-passkey') }}
                    </div>
                @endunless
            </div>

            <div class="flex items-center shrink-0 gap-x-1">
                <livewire:security-key-actions
                    :record="$securityKey"
                    :wire:key="'securityKey' . $securityKey->getRouteKey()"
                />
            </div>
        </div>
    @endforeach
</x-profile-filament::multi-factor.multi-factor-list>
