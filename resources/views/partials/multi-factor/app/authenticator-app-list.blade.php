<x-profile-filament::multi-factor.multi-factor-list
    :toggle-label="Lang::choice('profile-filament::auth/multi-factor/app/provider.management-schema.list.toggle-list', $authenticatorApps->count(), ['count' => $authenticatorApps->count()])"
>
    @foreach ($authenticatorApps as $app)
        <div class="py-3 last:pb-0 flex items-center justify-between">
            <div class="flex-auto min-w-0 pr-2">
                <div class="font-semibold text-sm">{{ $app->name }}</div>

                <div class="text-xs mt-1 text-neutral-600 dark:text-neutral-300">
                    <span>{{ $app->registered_at }}</span>
                    <span>|</span>
                    <span>{{ $app->last_used }}</span>
                </div>
            </div>

            <div class="flex items-center shrink-0 gap-x-1">
                <livewire:authenticator-app-actions
                    :authenticator-app="$app"
                    :wire:key="'authenticatorApp' . $app->getRouteKey()"
                />
            </div>
        </div>
    @endforeach
</x-profile-filament::multi-factor.multi-factor-list>
