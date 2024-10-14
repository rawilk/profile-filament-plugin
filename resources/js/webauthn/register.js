import {
    isFunction,
} from '../utils.js';

import mixin from './mixin';

import {
    browserSupportsWebAuthn,
    startRegistration,
} from '@simplewebauthn/browser';

const registerWebauthn = ({
    before = undefined,
    registerData = {},
    registerUrl = undefined,
    publicKey = undefined,
    verifyKeyMethod = 'verifyKey',
}) => ({
    before,
    registerData,
    registerUrl,
    publicKey,
    verifyKeyMethod,
    error: null,
    processing: false,
    browserSupportsWebAuthn,
    ...mixin,

    async register() {
        this.error = null;

        if (! this.browserSupportsWebAuthn()) {
            return;
        }

        if (isFunction(this.before)) {
            const callback = this.before.bind(this);
            const isValid = await callback();

            if (! isValid) {
                return;
            }
        }

        let publicKey = this.publicKey;
        this.processing = true;

        const registerData = isFunction(this.registerData)
            ? this.registerData()
            : this.registerData;

        if (this.registerUrl) {
            const response = await fetch(this.registerUrl, this._ajaxOptions(registerData));

            if (! response.ok) {
                this.processing = false;

                return this.notifyPublicKeyError();
            }

            publicKey = await response.json();
        }

        if (! this.isValidPublicKey(publicKey, 'rp')) {
            this.processing = false;

            return this.notifyPublicKeyError();
        }

        return startRegistration({ optionsJSON: publicKey })
            .then(resp => this.$wire.call(this.verifyKeyMethod, resp))
            .catch(error => this.error = error?.response?.data?.message ?? error)
            .finally(() => this.processing = false);
    },
});

export default registerWebauthn;
