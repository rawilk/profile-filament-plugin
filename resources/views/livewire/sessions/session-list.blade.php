<p class="text-sm" wire:ignore>{{ __('profile-filament::pages/sessions.manager.list_description') }}</p>

<div class="pf-session-list-ctn mt-4 divide-y border border-gray-300 dark:border-gray-600 rounded-md divide-gray-300 dark:divide-gray-600">
    @foreach ($sessions as $session)
        <x-profile-filament::sessions.session
            :session="$session"
            wire:key="session.{{ $session->id() }}"
            class="first:rounded-t-md last:rounded-b-md"
        >
            <x-slot:actions>
                @unless ($session->isCurrentDevice())
                    {{ ($logoutSingleSessionAction)(['record' => $session->id()]) }}
                @endunless
            </x-slot:actions>
        </x-profile-filament::sessions.session>
    @endforeach
</div>
