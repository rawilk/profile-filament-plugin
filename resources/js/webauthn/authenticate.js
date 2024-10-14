import mixin from './mixin';

import {
    isFunction,
} from '../utils';

import {
    browserSupportsWebAuthn,
    startAuthentication,
} from '@simplewebauthn/browser';

const authenticateWebauthn = ({
    publicKey = undefined,
    publicKeyUrl = undefined,
    loginMethod = 'authenticate',
    loginUsing = undefined,
}) => ({
    publicKey,
    publicKeyUrl,
    loginMethod,
    loginUsing,
    error: null,
    processing: false,
    browserSupportsWebAuthn,
    ...mixin,

    async login() {
        let publicKey = this.publicKey;

        this.processing = true;
        this.error = null;

        if (this.publicKeyUrl) {
            const response = await fetch(this.publicKeyUrl, this._ajaxOptions());

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

        return startAuthentication({ optionsJSON: publicKey })
            .then(async answer => {
                if (isFunction(this.loginUsing)) {
                    const callback = this.loginUsing.bind(this);
                    await callback(answer);

                    return;
                }

                await this.$wire.call(this.loginMethod, answer);
            })
            .catch(error => {
                this.error = error?.response?.data?.message ?? error;

                this.$wire.call('$refresh');
            })
            .finally(() => this.processing = false);
    },
});

export default authenticateWebauthn;
