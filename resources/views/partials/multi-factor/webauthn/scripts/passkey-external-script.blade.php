@php
    use Rawilk\ProfileFilament\Facades\ProfileFilament;
    use Rawilk\ProfileFilament\Support\Config as PackageConfig;
    use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;

    $externalAuthUrl = ProfileFilament::plugin()->getCrossDomainWebauthnAuthenticationUrl(
        user: null,
        originalHost: request()->getHost(),
        data: [
            'providerId' => 'webauthn',
            'passkey' => true,
            'nonce' => ProfileFilament::generateWebauthnNonce(),
        ],
    );

    $relyingPartyId = PackageConfig::getRelyingPartyId();

    $errors ??= null;
@endphp

x-data="{
    processing: false,
    hasErrors: false,
    isSupported: true,
    validationError: @js($errors?->first('passkey')),
    panel: @js(Crypt::encryptString($panel)),

    init() {
        this.isSupported = browserSupportsWebAuthn();

        if (this.validationError) {
            this.hasErrors = true;
        }
    },

    authenticate: async function () {
        this.processing = true;
        this.hasErrors = false;
        this.validationError = null;

        const width = 600;
        const height = 600;
        const left = window.screenX + ((window.innerWidth - width) / 2);
        const top = window.screenY + ((window.innerHeight - height) / 2);

        const authWindow = window.open(
            @js($externalAuthUrl),
            null,
            `width=${width},height=${height},top=${top},left=${left},status=no,menubar=no,toolbar=no`
        );

        if (! authWindow) {
            alert(@js(__('profile-filament::auth/multi-factor/webauthn/actions/auth-on-domain.form.messages.popups-disabled.passkey')));

            return;
        }

        window.addEventListener('message', function (event) {
            if (event.origin !== ('https://' + @js($relyingPartyId))) {
                return;
            }

            this.processing = false;

            if (event.data.type === 'webauthn-external-auth-success') {
                const form = document.getElementById('passkey-login-form');

                form.addEventListener('formdata', ({ formData }) => {
                    formData.append('passkeyResponse', JSON.stringify(event.data.authenticationResponse));
                    formData.append('_options', event.data.options);
                    formData.append('nonce', event.data.nonce);
                    formData.append('panel', this.panel);

                    {{-- we are assuming this is a default filament form here to grab the remember me value --}}
                    const rememberCheckbox = document.getElementById('form.remember');

                    rememberCheckbox && formData.append('remember', rememberCheckbox.checked);
                });

                form.submit();

                authWindow.close();
            }
        });

        const closeCheckInterval = setInterval(() => {
            if (! authWindow.closed) {
                return;
            }

            window.clearInterval(closeCheckInterval);

            this.processing = false;
        }, 500);
    },
}"
