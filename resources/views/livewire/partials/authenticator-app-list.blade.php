<div id="authenticator-apps-list" class="divide-y divide-gray-300 dark:divide-gray-600">
    @foreach ($this->sortedAuthenticatorApps as $registeredApp)
        <livewire:authenticator-app-list-item
            :app="$registeredApp"
            :key="'authenticatorApp' . $registeredApp->id"
        />
    @endforeach

    <div class="py-3">
        {{ $this->addAction }}
    </div>
</div>

<x-filament-actions::modals />
