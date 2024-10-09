<div id="authenticator-apps-list" class="divide-y divide-gray-300 dark:divide-gray-600">
    @foreach ($this->sortedAuthenticatorApps as $registeredApp)
        @livewire(\Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\AuthenticatorAppListItem::class, [
            'app' => $registeredApp,
        ], key('authenticatorApp' . $registeredApp->getKey()))
    @endforeach

    <div class="py-3">
        {{ $this->addAction }}
    </div>
</div>

<x-filament-actions::modals />
