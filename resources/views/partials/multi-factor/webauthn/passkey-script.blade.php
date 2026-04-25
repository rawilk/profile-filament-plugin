x-data="{
    processing: false,
    hasErrors: false,
    isSupported: true,
    validationError: @js($errors->first('passkey')),

    init() {
        this.isSupported = browserSupportsWebAuthn();

        if (this.validationError) {
            this.hasErrors = true;
        }
    },

    authenticate: async function() {
        this.processing = true;
        this.hasErrors = false;
        this.validationError = null;

        const response = await fetch('{{ route('profile-filament::webauthn.passkey_authentication_options') }}');

        const options = await response.json();

        const passkeyResponse = await startAuthentication({ optionsJSON: options })
            .catch(() => this.hasErrors = true)
            .finally(() => this.processing = false);

        const isArray = obj => Array.isArray(obj);
        const isObjectish = obj => typeof obj === 'object' && obj !== null;
        const isObject = obj => isObjectish(obj) && ! isArray(obj);
        const objectHasKey = (obj, key) => key in obj;

        if (! isObject(passkeyResponse)) {
            return;
        }

        if (! objectHasKey(passkeyResponse, 'id')) {
            return;
        }

        const form = document.getElementById('passkey-login-form');

        form.addEventListener('formdata', ({ formData }) => {
            formData.append('passkeyResponse', JSON.stringify(passkeyResponse));

            {{-- we are assuming this is a default filament form here to grab the remember me value --}}
            const rememberCheckbox = document.getElementById('form.remember');

            rememberCheckbox && formData.append('remember', rememberCheckbox.checked);
        });

        form.submit();
    },
}"
