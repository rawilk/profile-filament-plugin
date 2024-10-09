import {
    getCsrfToken,
    isObject,
    objectHasKey,
} from '../utils';

export default {
    hasErrors() {
        return Object.keys(
            this.$wire.__instance?.snapshot?.memo?.errors ?? {}
        ).length > 0;
    },

    notifyPublicKeyError() {
        new FilamentNotification()
            .danger()
            .title('Error')
            .body('We encountered a fatal error in the key generation process. Please try again later.')
            .send();
    },

    isValidPublicKey(publicKey, relyingPartyIdentifier = 'rpId') {
        return isObject(publicKey) &&
            objectHasKey(publicKey, 'challenge') &&
            objectHasKey(publicKey, relyingPartyIdentifier);
    },

    _ajaxOptions(data = {}) {
        return {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Webauthn': '',
            },
            body: JSON.stringify({
                _token: getCsrfToken(),
                ...data,
            }),
        };
    }
};
