<x-profile-filament::multi-factor.webauthn-auth
    :failed-text="$failedText"
    :livewire-id="$livewireId"
>
    Livewire.find(livewireId).call(
        'authenticate',
        {
            authenticationResponse: JSON.stringify(authenticationResponse),
        },
    );
</x-profile-filament::multi-factor.webauthn-auth>
