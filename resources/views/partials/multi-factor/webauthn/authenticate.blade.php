@props([
    'promptText' => null,
    'failedText' => null,
    'livewireId',
])

<x-profile-filament::multi-factor.webauthn-auth
    :prompt-text="$promptText"
    :failed-text="$failedText"
    :livewire-id="$livewireId"
>
    Livewire.find(livewireId).call(
        'mountAction',
        'authenticateWebauthn',
        {
            authenticationResponse: JSON.stringify(authenticationResponse),
        },
        @js([
            'schemaComponent' => 'form.webauthn',
        ])
    );

    <x-slot:scripts>
        Livewire.on('webauthnExternalAuth', function ([{ url, relyingPartyId }]) {
            const width = 600;
            const height = 600;
            const left = window.screenX + ((window.innerWidth - width) / 2);
            const top = window.screenY + ((window.innerHeight - height) / 2);

            const authWindow = window.open(
                url,
                null,
                `width=${width},height=${height},top=${top},left=${left},status=no,menubar=no,toolbar=no`
            );

            if (! authWindow) {
                alert(@js(__('profile-filament::auth/multi-factor/webauthn/actions/auth-on-domain.form.messages.popups-disabled.webauthn')));

                return;
            }

            window.addEventListener('message', function (event) {
                if (event.origin !== `https://${relyingPartyId}`) {
                    return;
                }

                if (event.data.type === 'webauthn-external-auth-success') {
                    Livewire.find(livewireId).call('authenticate', { userId: event.data.userId, challenge: event.data.challenge, nonce: event.data.nonce });

                    authWindow.close();
                }
            });
        });
    </x-slot:scripts>
</x-profile-filament::multi-factor.webauthn-auth>
