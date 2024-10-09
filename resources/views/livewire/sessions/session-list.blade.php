<p class="text-sm">{{ __('profile-filament::pages/sessions.manager.list_description') }}</p>

<div class="mt-4 divide-y border border-gray-300 dark:border-gray-600 rounded-md divide-gray-300 dark:divide-gray-600">
    @foreach ($this->sessions as $session)
        <div
            wire:key="session.{{ $session->id }}"
            class="first:rounded-t-md last:rounded-b-md bg-gray-50 dark:bg-gray-800"
        >
            <div class="px-6 py-4">
                <div class="flex justify-between items-start gap-x-4">
                    <div>
                        <div class="flex gap-x-4">
                            <div class="shrink-0">
                                <x-filament::icon
                                    :alias="$session->agent->isDesktop() ? 'session::desktop' : 'session::mobile'"
                                    :icon="$session->agent->isDesktop() ? 'heroicon-o-computer-desktop' : 'heroicon-o-device-phone-mobile'"
                                    class="h-8 w-8 text-gray-500 dark:text-gray-400"
                                />
                            </div>

                            <div class="flex-1">
                                <div class="text-sm">
                                    {{ $session->agent->platform() ?: __('profile-filament::pages/sessions.manager.unknown_platform') }}
                                    -
                                    {{ $session->agent->browser() ?: __('profile-filament::pages/sessions.manager.unknown_browser') }}
                                </div>

                                <div class="text-xs mt-1">
                                    <x-filament::link
                                        href="https://tools.keycdn.com/geo?host={{ $session->ip_address }}"
                                        :tooltip="__('profile-filament::pages/sessions.manager.ip_info_tooltip')"
                                        target="_blank"
                                        rel="nofollow noopener"
                                        class="text-xs font-normal"
                                    >
                                        {{ $session->ip_address }}
                                    </x-filament::link>

                                    <span aria-hidden="true">-</span>

                                    @if ($session->is_current_device)
                                        <span class="text-success-500 font-semibold">{{ __('profile-filament::pages/sessions.manager.current_device') }}</span>
                                    @else
                                        <span>{{ __('profile-filament::pages/sessions.manager.last_activity', ['time' => $session->last_active]) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- actions --}}
                    <div>
                        @unless ($session->is_current_device)
                            {{ ($this->revokeSessionAction)(['record' => $session->id]) }}
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
