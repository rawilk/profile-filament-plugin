<div id="authenticator-app-form">
    @if ($show)
        @includeWhen($authenticatorApps->isNotEmpty() && ! $showForm, 'profile-filament::livewire.partials.authenticator-app-list')

        @includeWhen($showForm, 'profile-filament::livewire.partials.add-authenticator-app')
    @endif
</div>
