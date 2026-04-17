@props([
    'session',
    'actions' => null,
])

@php
    use Filament\Support\Icons\Heroicon;
    use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
    /** @var \Rawilk\ProfileFilament\Dto\Sessions\Session $session */
@endphp

<div {{ $attributes->class(['pf-session-ctn bg-gray-50 dark:bg-gray-800']) }}>
    <div class="px-6 py-4">
        <div class="flex justify-between items-start gap-x-4">
            <div>
                <div class="flex gap-x-4">
                    <div class="shrink-0">
                        <x-filament::icon
                            :alias="$session->agent()->isDesktop() ? ProfileFilamentIcon::SessionDesktop->value : ProfileFilamentIcon::SessionMobile->value"
                            :icon="$session->agent()->isDesktop() ? Heroicon::OutlinedComputerDesktop : Heroicon::OutlinedDevicePhoneMobile"
                            class="h-8 w-8 text-gray-500 dark:text-gray-400"
                        />
                    </div>

                    <div class="flex-1">
                        <div class="text-sm">
                            {{ $session->agent()->platform() ?: __('profile-filament::pages/sessions.manager.unknown_platform') }}
                            -
                            {{ $session->agent()->browser() ?: __('profile-filament::pages/sessions.manager.unknown_browser') }}
                        </div>

                        <div class="text-xs mt-1">
                            <x-filament::link
                                href="https://tools.keycdn.com/geo?host={{ $session->ipAddress() }}"
                                :tooltip="__('profile-filament::pages/sessions.manager.ip_info_tooltip')"
                                target="_blank"
                                rel="nofollow noopener"
                                size="xs"
                                weight="normal"
                            >
                                {{ $session->ipAddress() }}
                            </x-filament::link>

                            <span aria-hidden="true">-</span>

                            @if ($session->isCurrentDevice())
                                <span class="text-success-500 font-semibold">{{ __('profile-filament::pages/sessions.manager.current_device') }}</span>
                            @else
                                <span>{{ __('profile-filament::pages/sessions.manager.last_activity', ['time' => $session->lastActive()]) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- actions --}}
            <div>
                {{ $actions }}
            </div>
        </div>
    </div>
</div>
