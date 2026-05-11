<x-profile-filament::multi-factor.webauthn-register>
    if (securityKey) {
        Livewire.find('{{ $livewireId }}').call('callMountedAction', { securityKey: JSON.stringify(securityKey) });
    }

    <x-slot:scripts>
        Livewire.on('webauthnExternalRegister', function ([{ url, relyingPartyId }]) {
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
                alert(@js(__('profile-filament::auth/multi-factor/webauthn/actions/register-on-domain.form.messages.popups-disabled')));

                return;
            }

            window.addEventListener('message', function (event) {
                if (event.origin !== `https://${relyingPartyId}`) {
                    return;
                }

                if (event.data.type === 'webauthn-external-success') {
                    Livewire.find('{{ $livewireId }}').call('callMountedAction', { userId: event.data.userId, securityKeyId: event.data.securityKeyId });

                    authWindow.close();
                }
            });
        });
    </x-slot:scripts>
</x-profile-filament::multi-factor.webauthn-register>
