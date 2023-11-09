import {
    startAuthentication,
    startRegistration,
    browserSupportsWebAuthn,
} from '@simplewebauthn/browser';

import {
    getCsrfToken,
    isObject,
    objectHasKey,
    isFunction,
} from './utils.js';

const fetchOptions = (data = {}) => ({
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Webauthn': '',
    },
    body: JSON.stringify({
        _token: getCsrfToken(),
        ...data,
    }),
});

export default function webauthnForm({
    mode = 'login',
    publicKey = undefined,
    wireId = undefined,
    beforeRegister = undefined,
    serverError = undefined,
    registerData = {},
    registerPublicKeyUrl = undefined,
    registerMethodName = 'verifyKey',
    loginPublicKeyUrl = undefined,
    loginMethodName = 'authenticate',
    loginUsing = undefined,
}) {
    return {
        mode,
        publicKey,
        wireId,
        beforeRegister,
        serverError,
        registerPublicKeyUrl,
        registerMethodName,
        registerData,
        loginPublicKeyUrl,
        loginMethodName,
        loginUsing,
        processing: false,
        browserSupported: browserSupportsWebAuthn(),
        error: null,

        async submit() {
            this.error = null;

            if (this.mode === 'login') {
                return this.submitLogin();
            }

            return this.submitRegister();
        },

        async submitRegister() {
            if (isFunction(this.beforeRegister)) {
                const isValid = await this.beforeRegister(this);

                if (! isValid) {
                    return;
                }
            }

            let publicKey = this.publicKey;
            this.processing = true;

            const registerData = isFunction(this.registerData)
                ? this.registerData()
                : this.registerData;

            if (this.registerPublicKeyUrl) {
                const response = await fetch(this.registerPublicKeyUrl, fetchOptions(registerData));

                if (! response.ok) {
                    this.processing = false;

                    return this.notifyPublicKeyError();
                }

                publicKey = await response.json();
            }

            if (! this.isValidPublicKey(publicKey)) {
                this.processing = false;

                return this.notifyPublicKeyError();
            }

            const component = window.Livewire.find(this.wireId);

            return startRegistration(publicKey)
                .then(resp => component.$call(this.registerMethodName, resp))
                .catch(error => this.error = error?.response?.data?.message || error)
                .finally(() => this.processing = false);
        },

        async submitLogin () {
            let publicKey = this.publicKey;
            this.processing = true;

            if (this.loginPublicKeyUrl) {
                const response = await fetch(this.loginPublicKeyUrl, fetchOptions());

                if (! response.ok) {
                    this.processing = false;

                    return this.notifyPublicKeyError();
                }

                publicKey = await response.json();
            }

            if (! this.isValidPublicKey(publicKey)) {
                this.processing = false;

                return this.notifyPublicKeyError();
            }

            const component = window.Livewire.find(this.wireId);

            this.error = null;
            this.serverError = false;

            return startAuthentication(publicKey)
                .then(resp => {
                    if (isFunction(this.loginUsing)) {
                        return this.loginUsing(resp);
                    }

                    return component.$call(this.loginMethodName, resp);
                })
                .catch(error => {
                    this.error = error?.response?.data?.message || error;
                    this.serverError = true;

                    component.$call('$refresh');
                })
                .finally(() => this.processing = false);
        },

        isValidPublicKey(publicKey) {
            return isObject(publicKey) &&
                objectHasKey(publicKey, 'challenge') &&
                objectHasKey(publicKey, this.mode === 'login' ? 'rpId' : 'rp');
        },

        notifyPublicKeyError() {
            new FilamentNotification()
                .danger()
                .title('Error')
                .body('We encountered a fatal error in the key generation process. Please try again later.')
                .send();
        },

        /**
         * Determine if any validation errors occurred in Livewire.
         *
         * I feel like there should be a better way to do this, but for now this works...
         */
        hasErrors(component) {
            return Object.keys(component?.__instance?.snapshot?.memo?.errors ?? {}).length > 0;
        },
    };
};
