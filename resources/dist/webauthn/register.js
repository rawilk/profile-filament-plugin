// resources/js/utils.js
var getCsrfToken = () => {
  if (document.querySelector('meta[name="csrf-token"]')) {
    return document.querySelector('meta[name="csrf-token"]').getAttribute("content");
  }
  if (document.querySelector("[data-csrf]")) {
    return document.querySelector("[data-csrf]").getAttribute("data-csrf");
  }
  if (window.livewireScriptConfig["csrf"] ?? false) {
    return window.livewireScriptConfig["csrf"];
  }
  throw new Error("No CSRF token detected");
};
var isArray = (obj) => Array.isArray(obj);
var isObjectish = (obj) => typeof obj === "object" && obj !== null;
var isObject = (obj) => isObjectish(obj) && !isArray(obj);
var isFunction = (func) => typeof func === "function";
var objectHasKey = (obj, key) => key in obj;

// resources/js/webauthn/mixin.js
var mixin_default = {
  hasErrors() {
    return Object.keys(
      this.$wire.__instance?.snapshot?.memo?.errors ?? {}
    ).length > 0;
  },
  notifyPublicKeyError() {
    new FilamentNotification().danger().title("Error").body("We encountered a fatal error in the key generation process. Please try again later.").send();
  },
  isValidPublicKey(publicKey, relyingPartyIdentifier = "rpId") {
    return isObject(publicKey) && objectHasKey(publicKey, "challenge") && objectHasKey(publicKey, relyingPartyIdentifier);
  },
  _ajaxOptions(data = {}) {
    return {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Webauthn": ""
      },
      body: JSON.stringify({
        _token: getCsrfToken(),
        ...data
      })
    };
  }
};

// node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js
function bufferToBase64URLString(buffer) {
  const bytes = new Uint8Array(buffer);
  let str = "";
  for (const charCode of bytes) {
    str += String.fromCharCode(charCode);
  }
  const base64String = btoa(str);
  return base64String.replace(/\+/g, "-").replace(/\//g, "_").replace(/=/g, "");
}

// node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js
function base64URLStringToBuffer(base64URLString) {
  const base64 = base64URLString.replace(/-/g, "+").replace(/_/g, "/");
  const padLength = (4 - base64.length % 4) % 4;
  const padded = base64.padEnd(base64.length + padLength, "=");
  const binary = atob(padded);
  const buffer = new ArrayBuffer(binary.length);
  const bytes = new Uint8Array(buffer);
  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return buffer;
}

// node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js
function browserSupportsWebAuthn() {
  return _browserSupportsWebAuthnInternals.stubThis(globalThis?.PublicKeyCredential !== void 0 && typeof globalThis.PublicKeyCredential === "function");
}
var _browserSupportsWebAuthnInternals = {
  stubThis: (value) => value
};

// node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js
function toPublicKeyCredentialDescriptor(descriptor) {
  const { id } = descriptor;
  return {
    ...descriptor,
    id: base64URLStringToBuffer(id),
    /**
     * `descriptor.transports` is an array of our `AuthenticatorTransportFuture` that includes newer
     * transports that TypeScript's DOM lib is ignorant of. Convince TS that our list of transports
     * are fine to pass to WebAuthn since browsers will recognize the new value.
     */
    transports: descriptor.transports
  };
}

// node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js
function isValidDomain(hostname) {
  return (
    // Consider localhost valid as well since it's okay wrt Secure Contexts
    hostname === "localhost" || // Support punycode (ACE) or ascii labels and domains
    /^((xn--[a-z0-9-]+|[a-z0-9]+(-[a-z0-9]+)*)\.)+([a-z]{2,}|xn--[a-z0-9-]+)$/i.test(hostname)
  );
}

// node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js
var WebAuthnError = class extends Error {
  constructor({ message, code, cause, name }) {
    super(message, { cause });
    Object.defineProperty(this, "code", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    this.name = name ?? cause.name;
    this.code = code;
  }
};

// node_modules/@simplewebauthn/browser/esm/helpers/identifyRegistrationError.js
function identifyRegistrationError({ error, options }) {
  const { publicKey } = options;
  if (!publicKey) {
    throw Error("options was missing required publicKey property");
  }
  if (error.name === "AbortError") {
    if (options.signal instanceof AbortSignal) {
      return new WebAuthnError({
        message: "Registration ceremony was sent an abort signal",
        code: "ERROR_CEREMONY_ABORTED",
        cause: error
      });
    }
  } else if (error.name === "ConstraintError") {
    if (publicKey.authenticatorSelection?.requireResidentKey === true) {
      return new WebAuthnError({
        message: "Discoverable credentials were required but no available authenticator supported it",
        code: "ERROR_AUTHENTICATOR_MISSING_DISCOVERABLE_CREDENTIAL_SUPPORT",
        cause: error
      });
    } else if (
      // @ts-ignore: `mediation` doesn't yet exist on CredentialCreationOptions but it's possible as of Sept 2024
      options.mediation === "conditional" && publicKey.authenticatorSelection?.userVerification === "required"
    ) {
      return new WebAuthnError({
        message: "User verification was required during automatic registration but it could not be performed",
        code: "ERROR_AUTO_REGISTER_USER_VERIFICATION_FAILURE",
        cause: error
      });
    } else if (publicKey.authenticatorSelection?.userVerification === "required") {
      return new WebAuthnError({
        message: "User verification was required but no available authenticator supported it",
        code: "ERROR_AUTHENTICATOR_MISSING_USER_VERIFICATION_SUPPORT",
        cause: error
      });
    }
  } else if (error.name === "InvalidStateError") {
    return new WebAuthnError({
      message: "The authenticator was previously registered",
      code: "ERROR_AUTHENTICATOR_PREVIOUSLY_REGISTERED",
      cause: error
    });
  } else if (error.name === "NotAllowedError") {
    return new WebAuthnError({
      message: error.message,
      code: "ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY",
      cause: error
    });
  } else if (error.name === "NotSupportedError") {
    const validPubKeyCredParams = publicKey.pubKeyCredParams.filter((param) => param.type === "public-key");
    if (validPubKeyCredParams.length === 0) {
      return new WebAuthnError({
        message: 'No entry in pubKeyCredParams was of type "public-key"',
        code: "ERROR_MALFORMED_PUBKEYCREDPARAMS",
        cause: error
      });
    }
    return new WebAuthnError({
      message: "No available authenticator supported any of the specified pubKeyCredParams algorithms",
      code: "ERROR_AUTHENTICATOR_NO_SUPPORTED_PUBKEYCREDPARAMS_ALG",
      cause: error
    });
  } else if (error.name === "SecurityError") {
    const effectiveDomain = globalThis.location.hostname;
    if (!isValidDomain(effectiveDomain)) {
      return new WebAuthnError({
        message: `${globalThis.location.hostname} is an invalid domain`,
        code: "ERROR_INVALID_DOMAIN",
        cause: error
      });
    } else if (publicKey.rp.id !== effectiveDomain) {
      return new WebAuthnError({
        message: `The RP ID "${publicKey.rp.id}" is invalid for this domain`,
        code: "ERROR_INVALID_RP_ID",
        cause: error
      });
    }
  } else if (error.name === "TypeError") {
    if (publicKey.user.id.byteLength < 1 || publicKey.user.id.byteLength > 64) {
      return new WebAuthnError({
        message: "User ID was not between 1 and 64 characters",
        code: "ERROR_INVALID_USER_ID_LENGTH",
        cause: error
      });
    }
  } else if (error.name === "UnknownError") {
    return new WebAuthnError({
      message: "The authenticator was unable to process the specified options, or could not create a new credential",
      code: "ERROR_AUTHENTICATOR_GENERAL_ERROR",
      cause: error
    });
  }
  return error;
}

// node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js
var BaseWebAuthnAbortService = class {
  constructor() {
    Object.defineProperty(this, "controller", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
  }
  createNewAbortSignal() {
    if (this.controller) {
      const abortError = new Error("Cancelling existing WebAuthn API call for new one");
      abortError.name = "AbortError";
      this.controller.abort(abortError);
    }
    const newController = new AbortController();
    this.controller = newController;
    return newController.signal;
  }
  cancelCeremony() {
    if (this.controller) {
      const abortError = new Error("Manually cancelling existing WebAuthn API call");
      abortError.name = "AbortError";
      this.controller.abort(abortError);
      this.controller = void 0;
    }
  }
};
var WebAuthnAbortService = new BaseWebAuthnAbortService();

// node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js
var attachments = ["cross-platform", "platform"];
function toAuthenticatorAttachment(attachment) {
  if (!attachment) {
    return;
  }
  if (attachments.indexOf(attachment) < 0) {
    return;
  }
  return attachment;
}

// node_modules/@simplewebauthn/browser/esm/methods/startRegistration.js
async function startRegistration(options) {
  if (!options.optionsJSON && options.challenge) {
    console.warn("startRegistration() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.");
    options = { optionsJSON: options };
  }
  const { optionsJSON, useAutoRegister = false } = options;
  if (!browserSupportsWebAuthn()) {
    throw new Error("WebAuthn is not supported in this browser");
  }
  const publicKey = {
    ...optionsJSON,
    challenge: base64URLStringToBuffer(optionsJSON.challenge),
    user: {
      ...optionsJSON.user,
      id: base64URLStringToBuffer(optionsJSON.user.id)
    },
    excludeCredentials: optionsJSON.excludeCredentials?.map(toPublicKeyCredentialDescriptor)
  };
  const createOptions = {};
  if (useAutoRegister) {
    createOptions.mediation = "conditional";
  }
  createOptions.publicKey = publicKey;
  createOptions.signal = WebAuthnAbortService.createNewAbortSignal();
  let credential;
  try {
    credential = await navigator.credentials.create(createOptions);
  } catch (err) {
    throw identifyRegistrationError({ error: err, options: createOptions });
  }
  if (!credential) {
    throw new Error("Registration was not completed");
  }
  const { id, rawId, response, type } = credential;
  let transports = void 0;
  if (typeof response.getTransports === "function") {
    transports = response.getTransports();
  }
  let responsePublicKeyAlgorithm = void 0;
  if (typeof response.getPublicKeyAlgorithm === "function") {
    try {
      responsePublicKeyAlgorithm = response.getPublicKeyAlgorithm();
    } catch (error) {
      warnOnBrokenImplementation("getPublicKeyAlgorithm()", error);
    }
  }
  let responsePublicKey = void 0;
  if (typeof response.getPublicKey === "function") {
    try {
      const _publicKey = response.getPublicKey();
      if (_publicKey !== null) {
        responsePublicKey = bufferToBase64URLString(_publicKey);
      }
    } catch (error) {
      warnOnBrokenImplementation("getPublicKey()", error);
    }
  }
  let responseAuthenticatorData;
  if (typeof response.getAuthenticatorData === "function") {
    try {
      responseAuthenticatorData = bufferToBase64URLString(response.getAuthenticatorData());
    } catch (error) {
      warnOnBrokenImplementation("getAuthenticatorData()", error);
    }
  }
  return {
    id,
    rawId: bufferToBase64URLString(rawId),
    response: {
      attestationObject: bufferToBase64URLString(response.attestationObject),
      clientDataJSON: bufferToBase64URLString(response.clientDataJSON),
      transports,
      publicKeyAlgorithm: responsePublicKeyAlgorithm,
      publicKey: responsePublicKey,
      authenticatorData: responseAuthenticatorData
    },
    type,
    clientExtensionResults: credential.getClientExtensionResults(),
    authenticatorAttachment: toAuthenticatorAttachment(credential.authenticatorAttachment)
  };
}
function warnOnBrokenImplementation(methodName, cause) {
  console.warn(`The browser extension that intercepted this WebAuthn API call incorrectly implemented ${methodName}. You should report this error to them.
`, cause);
}

// resources/js/webauthn/register.js
var registerWebauthn = ({
  before = void 0,
  registerData = {},
  registerUrl = void 0,
  publicKey = void 0,
  verifyKeyMethod = "verifyKey"
}) => ({
  before,
  registerData,
  registerUrl,
  publicKey,
  verifyKeyMethod,
  error: null,
  processing: false,
  browserSupportsWebAuthn,
  ...mixin_default,
  async register() {
    this.error = null;
    if (!this.browserSupportsWebAuthn()) {
      return;
    }
    if (isFunction(this.before)) {
      const callback = this.before.bind(this);
      const isValid = await callback();
      if (!isValid) {
        return;
      }
    }
    let publicKey2 = this.publicKey;
    this.processing = true;
    const registerData2 = isFunction(this.registerData) ? this.registerData() : this.registerData;
    if (this.registerUrl) {
      const response = await fetch(this.registerUrl, this._ajaxOptions(registerData2));
      if (!response.ok) {
        this.processing = false;
        return this.notifyPublicKeyError();
      }
      publicKey2 = await response.json();
    }
    if (!this.isValidPublicKey(publicKey2, "rp")) {
      this.processing = false;
      return this.notifyPublicKeyError();
    }
    return startRegistration({ optionsJSON: publicKey2 }).then((resp) => this.$wire.call(this.verifyKeyMethod, resp)).catch((error) => this.error = error?.response?.data?.message ?? error).finally(() => this.processing = false);
  }
});
var register_default = registerWebauthn;
export {
  register_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsiLi4vLi4vanMvdXRpbHMuanMiLCAiLi4vLi4vanMvd2ViYXV0aG4vbWl4aW4uanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL2J1ZmZlclRvQmFzZTY0VVJMU3RyaW5nLmpzIiwgIi4uLy4uLy4uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vaGVscGVycy9iYXNlNjRVUkxTdHJpbmdUb0J1ZmZlci5qcyIsICIuLi8uLi8uLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL2hlbHBlcnMvYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4uanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL3RvUHVibGljS2V5Q3JlZGVudGlhbERlc2NyaXB0b3IuanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL2lzVmFsaWREb21haW4uanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL3dlYkF1dGhuRXJyb3IuanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL2lkZW50aWZ5UmVnaXN0cmF0aW9uRXJyb3IuanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL3dlYkF1dGhuQWJvcnRTZXJ2aWNlLmpzIiwgIi4uLy4uLy4uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vaGVscGVycy90b0F1dGhlbnRpY2F0b3JBdHRhY2htZW50LmpzIiwgIi4uLy4uLy4uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vbWV0aG9kcy9zdGFydFJlZ2lzdHJhdGlvbi5qcyIsICIuLi8uLi9qcy93ZWJhdXRobi9yZWdpc3Rlci5qcyJdLAogICJzb3VyY2VzQ29udGVudCI6IFsiLy8gRGlyZWN0IGNvcHkgZnJvbSBMaXZld2lyZSdzIGpzXG5leHBvcnQgY29uc3QgZ2V0Q3NyZlRva2VuID0gKCkgPT4ge1xuICAgIGlmIChkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCdtZXRhW25hbWU9XCJjc3JmLXRva2VuXCJdJykpIHtcbiAgICAgICAgcmV0dXJuIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ21ldGFbbmFtZT1cImNzcmYtdG9rZW5cIl0nKS5nZXRBdHRyaWJ1dGUoJ2NvbnRlbnQnKVxuICAgIH1cblxuICAgIGlmIChkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1jc3JmXScpKSB7XG4gICAgICAgIHJldHVybiBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1jc3JmXScpLmdldEF0dHJpYnV0ZSgnZGF0YS1jc3JmJylcbiAgICB9XG5cbiAgICBpZiAod2luZG93LmxpdmV3aXJlU2NyaXB0Q29uZmlnWydjc3JmJ10gPz8gZmFsc2UpIHtcbiAgICAgICAgcmV0dXJuIHdpbmRvdy5saXZld2lyZVNjcmlwdENvbmZpZ1snY3NyZiddXG4gICAgfVxuXG4gICAgdGhyb3cgbmV3IEVycm9yKCdObyBDU1JGIHRva2VuIGRldGVjdGVkJyk7XG59O1xuXG5leHBvcnQgY29uc3QgaXNBcnJheSA9IG9iaiA9PiBBcnJheS5pc0FycmF5KG9iaik7XG5leHBvcnQgY29uc3QgaXNPYmplY3Rpc2ggPSBvYmogPT4gdHlwZW9mIG9iaiA9PT0gJ29iamVjdCcgJiYgb2JqICE9PSBudWxsO1xuZXhwb3J0IGNvbnN0IGlzT2JqZWN0ID0gb2JqID0+IGlzT2JqZWN0aXNoKG9iaikgJiYgISBpc0FycmF5KG9iaik7XG5leHBvcnQgY29uc3QgaXNGdW5jdGlvbiA9IGZ1bmMgPT4gdHlwZW9mIGZ1bmMgPT09ICdmdW5jdGlvbic7XG5cbmV4cG9ydCBjb25zdCBvYmplY3RIYXNLZXkgPSAob2JqLCBrZXkpID0+IGtleSBpbiBvYmo7XG4iLCAiaW1wb3J0IHtcbiAgICBnZXRDc3JmVG9rZW4sXG4gICAgaXNPYmplY3QsXG4gICAgb2JqZWN0SGFzS2V5LFxufSBmcm9tICcuLi91dGlscyc7XG5cbmV4cG9ydCBkZWZhdWx0IHtcbiAgICBoYXNFcnJvcnMoKSB7XG4gICAgICAgIHJldHVybiBPYmplY3Qua2V5cyhcbiAgICAgICAgICAgIHRoaXMuJHdpcmUuX19pbnN0YW5jZT8uc25hcHNob3Q/Lm1lbW8/LmVycm9ycyA/PyB7fVxuICAgICAgICApLmxlbmd0aCA+IDA7XG4gICAgfSxcblxuICAgIG5vdGlmeVB1YmxpY0tleUVycm9yKCkge1xuICAgICAgICBuZXcgRmlsYW1lbnROb3RpZmljYXRpb24oKVxuICAgICAgICAgICAgLmRhbmdlcigpXG4gICAgICAgICAgICAudGl0bGUoJ0Vycm9yJylcbiAgICAgICAgICAgIC5ib2R5KCdXZSBlbmNvdW50ZXJlZCBhIGZhdGFsIGVycm9yIGluIHRoZSBrZXkgZ2VuZXJhdGlvbiBwcm9jZXNzLiBQbGVhc2UgdHJ5IGFnYWluIGxhdGVyLicpXG4gICAgICAgICAgICAuc2VuZCgpO1xuICAgIH0sXG5cbiAgICBpc1ZhbGlkUHVibGljS2V5KHB1YmxpY0tleSwgcmVseWluZ1BhcnR5SWRlbnRpZmllciA9ICdycElkJykge1xuICAgICAgICByZXR1cm4gaXNPYmplY3QocHVibGljS2V5KSAmJlxuICAgICAgICAgICAgb2JqZWN0SGFzS2V5KHB1YmxpY0tleSwgJ2NoYWxsZW5nZScpICYmXG4gICAgICAgICAgICBvYmplY3RIYXNLZXkocHVibGljS2V5LCByZWx5aW5nUGFydHlJZGVudGlmaWVyKTtcbiAgICB9LFxuXG4gICAgX2FqYXhPcHRpb25zKGRhdGEgPSB7fSkge1xuICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgICAgICBoZWFkZXJzOiB7XG4gICAgICAgICAgICAgICAgJ0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi9qc29uJyxcbiAgICAgICAgICAgICAgICAnWC1XZWJhdXRobic6ICcnLFxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGJvZHk6IEpTT04uc3RyaW5naWZ5KHtcbiAgICAgICAgICAgICAgICBfdG9rZW46IGdldENzcmZUb2tlbigpLFxuICAgICAgICAgICAgICAgIC4uLmRhdGEsXG4gICAgICAgICAgICB9KSxcbiAgICAgICAgfTtcbiAgICB9XG59O1xuIiwgIi8qKlxuICogQ29udmVydCB0aGUgZ2l2ZW4gYXJyYXkgYnVmZmVyIGludG8gYSBCYXNlNjRVUkwtZW5jb2RlZCBzdHJpbmcuIElkZWFsIGZvciBjb252ZXJ0aW5nIHZhcmlvdXNcbiAqIGNyZWRlbnRpYWwgcmVzcG9uc2UgQXJyYXlCdWZmZXJzIHRvIHN0cmluZyBmb3Igc2VuZGluZyBiYWNrIHRvIHRoZSBzZXJ2ZXIgYXMgSlNPTi5cbiAqXG4gKiBIZWxwZXIgbWV0aG9kIHRvIGNvbXBsaW1lbnQgYGJhc2U2NFVSTFN0cmluZ1RvQnVmZmVyYFxuICovXG5leHBvcnQgZnVuY3Rpb24gYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcoYnVmZmVyKSB7XG4gICAgY29uc3QgYnl0ZXMgPSBuZXcgVWludDhBcnJheShidWZmZXIpO1xuICAgIGxldCBzdHIgPSAnJztcbiAgICBmb3IgKGNvbnN0IGNoYXJDb2RlIG9mIGJ5dGVzKSB7XG4gICAgICAgIHN0ciArPSBTdHJpbmcuZnJvbUNoYXJDb2RlKGNoYXJDb2RlKTtcbiAgICB9XG4gICAgY29uc3QgYmFzZTY0U3RyaW5nID0gYnRvYShzdHIpO1xuICAgIHJldHVybiBiYXNlNjRTdHJpbmcucmVwbGFjZSgvXFwrL2csICctJykucmVwbGFjZSgvXFwvL2csICdfJykucmVwbGFjZSgvPS9nLCAnJyk7XG59XG4iLCAiLyoqXG4gKiBDb252ZXJ0IGZyb20gYSBCYXNlNjRVUkwtZW5jb2RlZCBzdHJpbmcgdG8gYW4gQXJyYXkgQnVmZmVyLiBCZXN0IHVzZWQgd2hlbiBjb252ZXJ0aW5nIGFcbiAqIGNyZWRlbnRpYWwgSUQgZnJvbSBhIEpTT04gc3RyaW5nIHRvIGFuIEFycmF5QnVmZmVyLCBsaWtlIGluIGFsbG93Q3JlZGVudGlhbHMgb3JcbiAqIGV4Y2x1ZGVDcmVkZW50aWFsc1xuICpcbiAqIEhlbHBlciBtZXRob2QgdG8gY29tcGxpbWVudCBgYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmdgXG4gKi9cbmV4cG9ydCBmdW5jdGlvbiBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlcihiYXNlNjRVUkxTdHJpbmcpIHtcbiAgICAvLyBDb252ZXJ0IGZyb20gQmFzZTY0VVJMIHRvIEJhc2U2NFxuICAgIGNvbnN0IGJhc2U2NCA9IGJhc2U2NFVSTFN0cmluZy5yZXBsYWNlKC8tL2csICcrJykucmVwbGFjZSgvXy9nLCAnLycpO1xuICAgIC8qKlxuICAgICAqIFBhZCB3aXRoICc9JyB1bnRpbCBpdCdzIGEgbXVsdGlwbGUgb2YgZm91clxuICAgICAqICg0IC0gKDg1ICUgNCA9IDEpID0gMykgJSA0ID0gMyBwYWRkaW5nXG4gICAgICogKDQgLSAoODYgJSA0ID0gMikgPSAyKSAlIDQgPSAyIHBhZGRpbmdcbiAgICAgKiAoNCAtICg4NyAlIDQgPSAzKSA9IDEpICUgNCA9IDEgcGFkZGluZ1xuICAgICAqICg0IC0gKDg4ICUgNCA9IDApID0gNCkgJSA0ID0gMCBwYWRkaW5nXG4gICAgICovXG4gICAgY29uc3QgcGFkTGVuZ3RoID0gKDQgLSAoYmFzZTY0Lmxlbmd0aCAlIDQpKSAlIDQ7XG4gICAgY29uc3QgcGFkZGVkID0gYmFzZTY0LnBhZEVuZChiYXNlNjQubGVuZ3RoICsgcGFkTGVuZ3RoLCAnPScpO1xuICAgIC8vIENvbnZlcnQgdG8gYSBiaW5hcnkgc3RyaW5nXG4gICAgY29uc3QgYmluYXJ5ID0gYXRvYihwYWRkZWQpO1xuICAgIC8vIENvbnZlcnQgYmluYXJ5IHN0cmluZyB0byBidWZmZXJcbiAgICBjb25zdCBidWZmZXIgPSBuZXcgQXJyYXlCdWZmZXIoYmluYXJ5Lmxlbmd0aCk7XG4gICAgY29uc3QgYnl0ZXMgPSBuZXcgVWludDhBcnJheShidWZmZXIpO1xuICAgIGZvciAobGV0IGkgPSAwOyBpIDwgYmluYXJ5Lmxlbmd0aDsgaSsrKSB7XG4gICAgICAgIGJ5dGVzW2ldID0gYmluYXJ5LmNoYXJDb2RlQXQoaSk7XG4gICAgfVxuICAgIHJldHVybiBidWZmZXI7XG59XG4iLCAiLyoqXG4gKiBEZXRlcm1pbmUgaWYgdGhlIGJyb3dzZXIgaXMgY2FwYWJsZSBvZiBXZWJhdXRoblxuICovXG5leHBvcnQgZnVuY3Rpb24gYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4oKSB7XG4gICAgcmV0dXJuIF9icm93c2VyU3VwcG9ydHNXZWJBdXRobkludGVybmFscy5zdHViVGhpcyhnbG9iYWxUaGlzPy5QdWJsaWNLZXlDcmVkZW50aWFsICE9PSB1bmRlZmluZWQgJiZcbiAgICAgICAgdHlwZW9mIGdsb2JhbFRoaXMuUHVibGljS2V5Q3JlZGVudGlhbCA9PT0gJ2Z1bmN0aW9uJyk7XG59XG4vKipcbiAqIE1ha2UgaXQgcG9zc2libGUgdG8gc3R1YiB0aGUgcmV0dXJuIHZhbHVlIGR1cmluZyB0ZXN0aW5nXG4gKiBAaWdub3JlIERvbid0IGluY2x1ZGUgdGhpcyBpbiBkb2NzIG91dHB1dFxuICovXG5leHBvcnQgY29uc3QgX2Jyb3dzZXJTdXBwb3J0c1dlYkF1dGhuSW50ZXJuYWxzID0ge1xuICAgIHN0dWJUaGlzOiAodmFsdWUpID0+IHZhbHVlLFxufTtcbiIsICJpbXBvcnQgeyBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlciB9IGZyb20gJy4vYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIuanMnO1xuZXhwb3J0IGZ1bmN0aW9uIHRvUHVibGljS2V5Q3JlZGVudGlhbERlc2NyaXB0b3IoZGVzY3JpcHRvcikge1xuICAgIGNvbnN0IHsgaWQgfSA9IGRlc2NyaXB0b3I7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgLi4uZGVzY3JpcHRvcixcbiAgICAgICAgaWQ6IGJhc2U2NFVSTFN0cmluZ1RvQnVmZmVyKGlkKSxcbiAgICAgICAgLyoqXG4gICAgICAgICAqIGBkZXNjcmlwdG9yLnRyYW5zcG9ydHNgIGlzIGFuIGFycmF5IG9mIG91ciBgQXV0aGVudGljYXRvclRyYW5zcG9ydEZ1dHVyZWAgdGhhdCBpbmNsdWRlcyBuZXdlclxuICAgICAgICAgKiB0cmFuc3BvcnRzIHRoYXQgVHlwZVNjcmlwdCdzIERPTSBsaWIgaXMgaWdub3JhbnQgb2YuIENvbnZpbmNlIFRTIHRoYXQgb3VyIGxpc3Qgb2YgdHJhbnNwb3J0c1xuICAgICAgICAgKiBhcmUgZmluZSB0byBwYXNzIHRvIFdlYkF1dGhuIHNpbmNlIGJyb3dzZXJzIHdpbGwgcmVjb2duaXplIHRoZSBuZXcgdmFsdWUuXG4gICAgICAgICAqL1xuICAgICAgICB0cmFuc3BvcnRzOiBkZXNjcmlwdG9yLnRyYW5zcG9ydHMsXG4gICAgfTtcbn1cbiIsICIvKipcbiAqIEEgc2ltcGxlIHRlc3QgdG8gZGV0ZXJtaW5lIGlmIGEgaG9zdG5hbWUgaXMgYSBwcm9wZXJseS1mb3JtYXR0ZWQgZG9tYWluIG5hbWVcbiAqXG4gKiBBIFwidmFsaWQgZG9tYWluXCIgaXMgZGVmaW5lZCBoZXJlOiBodHRwczovL3VybC5zcGVjLndoYXR3Zy5vcmcvI3ZhbGlkLWRvbWFpblxuICpcbiAqIFJlZ2V4IHdhcyBvcmlnaW5hbGx5IHNvdXJjZWQgZnJvbSBoZXJlLCB0aGVuIHJlbWl4ZWQgdG8gYWRkIHB1bnljb2RlIHN1cHBvcnQ6XG4gKiBodHRwczovL3d3dy5vcmVpbGx5LmNvbS9saWJyYXJ5L3ZpZXcvcmVndWxhci1leHByZXNzaW9ucy1jb29rYm9vay85NzgxNDQ5MzI3NDUzL2NoMDhzMTUuaHRtbFxuICovXG5leHBvcnQgZnVuY3Rpb24gaXNWYWxpZERvbWFpbihob3N0bmFtZSkge1xuICAgIHJldHVybiAoXG4gICAgLy8gQ29uc2lkZXIgbG9jYWxob3N0IHZhbGlkIGFzIHdlbGwgc2luY2UgaXQncyBva2F5IHdydCBTZWN1cmUgQ29udGV4dHNcbiAgICBob3N0bmFtZSA9PT0gJ2xvY2FsaG9zdCcgfHxcbiAgICAgICAgLy8gU3VwcG9ydCBwdW55Y29kZSAoQUNFKSBvciBhc2NpaSBsYWJlbHMgYW5kIGRvbWFpbnNcbiAgICAgICAgL14oKHhuLS1bYS16MC05LV0rfFthLXowLTldKygtW2EtejAtOV0rKSopXFwuKSsoW2Etel17Mix9fHhuLS1bYS16MC05LV0rKSQvaS50ZXN0KGhvc3RuYW1lKSk7XG59XG4iLCAiLyoqXG4gKiBBIGN1c3RvbSBFcnJvciB1c2VkIHRvIHJldHVybiBhIG1vcmUgbnVhbmNlZCBlcnJvciBkZXRhaWxpbmcgX3doeV8gb25lIG9mIHRoZSBlaWdodCBkb2N1bWVudGVkXG4gKiBlcnJvcnMgaW4gdGhlIHNwZWMgd2FzIHJhaXNlZCBhZnRlciBjYWxsaW5nIGBuYXZpZ2F0b3IuY3JlZGVudGlhbHMuY3JlYXRlKClgIG9yXG4gKiBgbmF2aWdhdG9yLmNyZWRlbnRpYWxzLmdldCgpYDpcbiAqXG4gKiAtIGBBYm9ydEVycm9yYFxuICogLSBgQ29uc3RyYWludEVycm9yYFxuICogLSBgSW52YWxpZFN0YXRlRXJyb3JgXG4gKiAtIGBOb3RBbGxvd2VkRXJyb3JgXG4gKiAtIGBOb3RTdXBwb3J0ZWRFcnJvcmBcbiAqIC0gYFNlY3VyaXR5RXJyb3JgXG4gKiAtIGBUeXBlRXJyb3JgXG4gKiAtIGBVbmtub3duRXJyb3JgXG4gKlxuICogRXJyb3IgbWVzc2FnZXMgd2VyZSBkZXRlcm1pbmVkIHRocm91Z2ggaW52ZXN0aWdhdGlvbiBvZiB0aGUgc3BlYyB0byBkZXRlcm1pbmUgdW5kZXIgd2hpY2hcbiAqIHNjZW5hcmlvcyBhIGdpdmVuIGVycm9yIHdvdWxkIGJlIHJhaXNlZC5cbiAqL1xuZXhwb3J0IGNsYXNzIFdlYkF1dGhuRXJyb3IgZXh0ZW5kcyBFcnJvciB7XG4gICAgY29uc3RydWN0b3IoeyBtZXNzYWdlLCBjb2RlLCBjYXVzZSwgbmFtZSwgfSkge1xuICAgICAgICAvLyBAdHMtaWdub3JlOiBoZWxwIFJvbGx1cCB1bmRlcnN0YW5kIHRoYXQgYGNhdXNlYCBpcyBva2F5IHRvIHNldFxuICAgICAgICBzdXBlcihtZXNzYWdlLCB7IGNhdXNlIH0pO1xuICAgICAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkodGhpcywgXCJjb2RlXCIsIHtcbiAgICAgICAgICAgIGVudW1lcmFibGU6IHRydWUsXG4gICAgICAgICAgICBjb25maWd1cmFibGU6IHRydWUsXG4gICAgICAgICAgICB3cml0YWJsZTogdHJ1ZSxcbiAgICAgICAgICAgIHZhbHVlOiB2b2lkIDBcbiAgICAgICAgfSk7XG4gICAgICAgIHRoaXMubmFtZSA9IG5hbWUgPz8gY2F1c2UubmFtZTtcbiAgICAgICAgdGhpcy5jb2RlID0gY29kZTtcbiAgICB9XG59XG4iLCAiaW1wb3J0IHsgaXNWYWxpZERvbWFpbiB9IGZyb20gJy4vaXNWYWxpZERvbWFpbi5qcyc7XG5pbXBvcnQgeyBXZWJBdXRobkVycm9yIH0gZnJvbSAnLi93ZWJBdXRobkVycm9yLmpzJztcbi8qKlxuICogQXR0ZW1wdCB0byBpbnR1aXQgX3doeV8gYW4gZXJyb3Igd2FzIHJhaXNlZCBhZnRlciBjYWxsaW5nIGBuYXZpZ2F0b3IuY3JlZGVudGlhbHMuY3JlYXRlKClgXG4gKi9cbmV4cG9ydCBmdW5jdGlvbiBpZGVudGlmeVJlZ2lzdHJhdGlvbkVycm9yKHsgZXJyb3IsIG9wdGlvbnMsIH0pIHtcbiAgICBjb25zdCB7IHB1YmxpY0tleSB9ID0gb3B0aW9ucztcbiAgICBpZiAoIXB1YmxpY0tleSkge1xuICAgICAgICB0aHJvdyBFcnJvcignb3B0aW9ucyB3YXMgbWlzc2luZyByZXF1aXJlZCBwdWJsaWNLZXkgcHJvcGVydHknKTtcbiAgICB9XG4gICAgaWYgKGVycm9yLm5hbWUgPT09ICdBYm9ydEVycm9yJykge1xuICAgICAgICBpZiAob3B0aW9ucy5zaWduYWwgaW5zdGFuY2VvZiBBYm9ydFNpZ25hbCkge1xuICAgICAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tY3JlYXRlQ3JlZGVudGlhbCAoU3RlcCAxNilcbiAgICAgICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICAgICAgbWVzc2FnZTogJ1JlZ2lzdHJhdGlvbiBjZXJlbW9ueSB3YXMgc2VudCBhbiBhYm9ydCBzaWduYWwnLFxuICAgICAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9DRVJFTU9OWV9BQk9SVEVEJyxcbiAgICAgICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBlbHNlIGlmIChlcnJvci5uYW1lID09PSAnQ29uc3RyYWludEVycm9yJykge1xuICAgICAgICBpZiAocHVibGljS2V5LmF1dGhlbnRpY2F0b3JTZWxlY3Rpb24/LnJlcXVpcmVSZXNpZGVudEtleSA9PT0gdHJ1ZSkge1xuICAgICAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tb3AtbWFrZS1jcmVkIChTdGVwIDQpXG4gICAgICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgICAgIG1lc3NhZ2U6ICdEaXNjb3ZlcmFibGUgY3JlZGVudGlhbHMgd2VyZSByZXF1aXJlZCBidXQgbm8gYXZhaWxhYmxlIGF1dGhlbnRpY2F0b3Igc3VwcG9ydGVkIGl0JyxcbiAgICAgICAgICAgICAgICBjb2RlOiAnRVJST1JfQVVUSEVOVElDQVRPUl9NSVNTSU5HX0RJU0NPVkVSQUJMRV9DUkVERU5USUFMX1NVUFBPUlQnLFxuICAgICAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKFxuICAgICAgICAvLyBAdHMtaWdub3JlOiBgbWVkaWF0aW9uYCBkb2Vzbid0IHlldCBleGlzdCBvbiBDcmVkZW50aWFsQ3JlYXRpb25PcHRpb25zIGJ1dCBpdCdzIHBvc3NpYmxlIGFzIG9mIFNlcHQgMjAyNFxuICAgICAgICBvcHRpb25zLm1lZGlhdGlvbiA9PT0gJ2NvbmRpdGlvbmFsJyAmJlxuICAgICAgICAgICAgcHVibGljS2V5LmF1dGhlbnRpY2F0b3JTZWxlY3Rpb24/LnVzZXJWZXJpZmljYXRpb24gPT09ICdyZXF1aXJlZCcpIHtcbiAgICAgICAgICAgIC8vIGh0dHBzOi8vdzNjLmdpdGh1Yi5pby93ZWJhdXRobi8jc2N0bi1jcmVhdGVDcmVkZW50aWFsIChTdGVwIDIyLjQpXG4gICAgICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgICAgIG1lc3NhZ2U6ICdVc2VyIHZlcmlmaWNhdGlvbiB3YXMgcmVxdWlyZWQgZHVyaW5nIGF1dG9tYXRpYyByZWdpc3RyYXRpb24gYnV0IGl0IGNvdWxkIG5vdCBiZSBwZXJmb3JtZWQnLFxuICAgICAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9BVVRPX1JFR0lTVEVSX1VTRVJfVkVSSUZJQ0FUSU9OX0ZBSUxVUkUnLFxuICAgICAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKHB1YmxpY0tleS5hdXRoZW50aWNhdG9yU2VsZWN0aW9uPy51c2VyVmVyaWZpY2F0aW9uID09PSAncmVxdWlyZWQnKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1vcC1tYWtlLWNyZWQgKFN0ZXAgNSlcbiAgICAgICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICAgICAgbWVzc2FnZTogJ1VzZXIgdmVyaWZpY2F0aW9uIHdhcyByZXF1aXJlZCBidXQgbm8gYXZhaWxhYmxlIGF1dGhlbnRpY2F0b3Igc3VwcG9ydGVkIGl0JyxcbiAgICAgICAgICAgICAgICBjb2RlOiAnRVJST1JfQVVUSEVOVElDQVRPUl9NSVNTSU5HX1VTRVJfVkVSSUZJQ0FUSU9OX1NVUFBPUlQnLFxuICAgICAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsc2UgaWYgKGVycm9yLm5hbWUgPT09ICdJbnZhbGlkU3RhdGVFcnJvcicpIHtcbiAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tY3JlYXRlQ3JlZGVudGlhbCAoU3RlcCAyMClcbiAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tb3AtbWFrZS1jcmVkIChTdGVwIDMpXG4gICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICBtZXNzYWdlOiAnVGhlIGF1dGhlbnRpY2F0b3Igd2FzIHByZXZpb3VzbHkgcmVnaXN0ZXJlZCcsXG4gICAgICAgICAgICBjb2RlOiAnRVJST1JfQVVUSEVOVElDQVRPUl9QUkVWSU9VU0xZX1JFR0lTVEVSRUQnLFxuICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICB9KTtcbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ05vdEFsbG93ZWRFcnJvcicpIHtcbiAgICAgICAgLyoqXG4gICAgICAgICAqIFBhc3MgdGhlIGVycm9yIGRpcmVjdGx5IHRocm91Z2guIFBsYXRmb3JtcyBhcmUgb3ZlcmxvYWRpbmcgdGhpcyBlcnJvciBiZXlvbmQgd2hhdCB0aGUgc3BlY1xuICAgICAgICAgKiBkZWZpbmVzIGFuZCB3ZSBkb24ndCB3YW50IHRvIG92ZXJ3cml0ZSBwb3RlbnRpYWxseSB1c2VmdWwgZXJyb3IgbWVzc2FnZXMuXG4gICAgICAgICAqL1xuICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgbWVzc2FnZTogZXJyb3IubWVzc2FnZSxcbiAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9QQVNTVEhST1VHSF9TRUVfQ0FVU0VfUFJPUEVSVFknLFxuICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICB9KTtcbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ05vdFN1cHBvcnRlZEVycm9yJykge1xuICAgICAgICBjb25zdCB2YWxpZFB1YktleUNyZWRQYXJhbXMgPSBwdWJsaWNLZXkucHViS2V5Q3JlZFBhcmFtcy5maWx0ZXIoKHBhcmFtKSA9PiBwYXJhbS50eXBlID09PSAncHVibGljLWtleScpO1xuICAgICAgICBpZiAodmFsaWRQdWJLZXlDcmVkUGFyYW1zLmxlbmd0aCA9PT0gMCkge1xuICAgICAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tY3JlYXRlQ3JlZGVudGlhbCAoU3RlcCAxMClcbiAgICAgICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICAgICAgbWVzc2FnZTogJ05vIGVudHJ5IGluIHB1YktleUNyZWRQYXJhbXMgd2FzIG9mIHR5cGUgXCJwdWJsaWMta2V5XCInLFxuICAgICAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9NQUxGT1JNRURfUFVCS0VZQ1JFRFBBUkFNUycsXG4gICAgICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tb3AtbWFrZS1jcmVkIChTdGVwIDIpXG4gICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICBtZXNzYWdlOiAnTm8gYXZhaWxhYmxlIGF1dGhlbnRpY2F0b3Igc3VwcG9ydGVkIGFueSBvZiB0aGUgc3BlY2lmaWVkIHB1YktleUNyZWRQYXJhbXMgYWxnb3JpdGhtcycsXG4gICAgICAgICAgICBjb2RlOiAnRVJST1JfQVVUSEVOVElDQVRPUl9OT19TVVBQT1JURURfUFVCS0VZQ1JFRFBBUkFNU19BTEcnLFxuICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICB9KTtcbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ1NlY3VyaXR5RXJyb3InKSB7XG4gICAgICAgIGNvbnN0IGVmZmVjdGl2ZURvbWFpbiA9IGdsb2JhbFRoaXMubG9jYXRpb24uaG9zdG5hbWU7XG4gICAgICAgIGlmICghaXNWYWxpZERvbWFpbihlZmZlY3RpdmVEb21haW4pKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1jcmVhdGVDcmVkZW50aWFsIChTdGVwIDcpXG4gICAgICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgICAgIG1lc3NhZ2U6IGAke2dsb2JhbFRoaXMubG9jYXRpb24uaG9zdG5hbWV9IGlzIGFuIGludmFsaWQgZG9tYWluYCxcbiAgICAgICAgICAgICAgICBjb2RlOiAnRVJST1JfSU5WQUxJRF9ET01BSU4nLFxuICAgICAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKHB1YmxpY0tleS5ycC5pZCAhPT0gZWZmZWN0aXZlRG9tYWluKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1jcmVhdGVDcmVkZW50aWFsIChTdGVwIDgpXG4gICAgICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgICAgIG1lc3NhZ2U6IGBUaGUgUlAgSUQgXCIke3B1YmxpY0tleS5ycC5pZH1cIiBpcyBpbnZhbGlkIGZvciB0aGlzIGRvbWFpbmAsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0lOVkFMSURfUlBfSUQnLFxuICAgICAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsc2UgaWYgKGVycm9yLm5hbWUgPT09ICdUeXBlRXJyb3InKSB7XG4gICAgICAgIGlmIChwdWJsaWNLZXkudXNlci5pZC5ieXRlTGVuZ3RoIDwgMSB8fCBwdWJsaWNLZXkudXNlci5pZC5ieXRlTGVuZ3RoID4gNjQpIHtcbiAgICAgICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLWNyZWF0ZUNyZWRlbnRpYWwgKFN0ZXAgNSlcbiAgICAgICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICAgICAgbWVzc2FnZTogJ1VzZXIgSUQgd2FzIG5vdCBiZXR3ZWVuIDEgYW5kIDY0IGNoYXJhY3RlcnMnLFxuICAgICAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9JTlZBTElEX1VTRVJfSURfTEVOR1RIJyxcbiAgICAgICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBlbHNlIGlmIChlcnJvci5uYW1lID09PSAnVW5rbm93bkVycm9yJykge1xuICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1vcC1tYWtlLWNyZWQgKFN0ZXAgMSlcbiAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tb3AtbWFrZS1jcmVkIChTdGVwIDgpXG4gICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICBtZXNzYWdlOiAnVGhlIGF1dGhlbnRpY2F0b3Igd2FzIHVuYWJsZSB0byBwcm9jZXNzIHRoZSBzcGVjaWZpZWQgb3B0aW9ucywgb3IgY291bGQgbm90IGNyZWF0ZSBhIG5ldyBjcmVkZW50aWFsJyxcbiAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9BVVRIRU5USUNBVE9SX0dFTkVSQUxfRVJST1InLFxuICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICB9KTtcbiAgICB9XG4gICAgcmV0dXJuIGVycm9yO1xufVxuIiwgImNsYXNzIEJhc2VXZWJBdXRobkFib3J0U2VydmljZSB7XG4gICAgY29uc3RydWN0b3IoKSB7XG4gICAgICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eSh0aGlzLCBcImNvbnRyb2xsZXJcIiwge1xuICAgICAgICAgICAgZW51bWVyYWJsZTogdHJ1ZSxcbiAgICAgICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZSxcbiAgICAgICAgICAgIHdyaXRhYmxlOiB0cnVlLFxuICAgICAgICAgICAgdmFsdWU6IHZvaWQgMFxuICAgICAgICB9KTtcbiAgICB9XG4gICAgY3JlYXRlTmV3QWJvcnRTaWduYWwoKSB7XG4gICAgICAgIC8vIEFib3J0IGFueSBleGlzdGluZyBjYWxscyB0byBuYXZpZ2F0b3IuY3JlZGVudGlhbHMuY3JlYXRlKCkgb3IgbmF2aWdhdG9yLmNyZWRlbnRpYWxzLmdldCgpXG4gICAgICAgIGlmICh0aGlzLmNvbnRyb2xsZXIpIHtcbiAgICAgICAgICAgIGNvbnN0IGFib3J0RXJyb3IgPSBuZXcgRXJyb3IoJ0NhbmNlbGxpbmcgZXhpc3RpbmcgV2ViQXV0aG4gQVBJIGNhbGwgZm9yIG5ldyBvbmUnKTtcbiAgICAgICAgICAgIGFib3J0RXJyb3IubmFtZSA9ICdBYm9ydEVycm9yJztcbiAgICAgICAgICAgIHRoaXMuY29udHJvbGxlci5hYm9ydChhYm9ydEVycm9yKTtcbiAgICAgICAgfVxuICAgICAgICBjb25zdCBuZXdDb250cm9sbGVyID0gbmV3IEFib3J0Q29udHJvbGxlcigpO1xuICAgICAgICB0aGlzLmNvbnRyb2xsZXIgPSBuZXdDb250cm9sbGVyO1xuICAgICAgICByZXR1cm4gbmV3Q29udHJvbGxlci5zaWduYWw7XG4gICAgfVxuICAgIGNhbmNlbENlcmVtb255KCkge1xuICAgICAgICBpZiAodGhpcy5jb250cm9sbGVyKSB7XG4gICAgICAgICAgICBjb25zdCBhYm9ydEVycm9yID0gbmV3IEVycm9yKCdNYW51YWxseSBjYW5jZWxsaW5nIGV4aXN0aW5nIFdlYkF1dGhuIEFQSSBjYWxsJyk7XG4gICAgICAgICAgICBhYm9ydEVycm9yLm5hbWUgPSAnQWJvcnRFcnJvcic7XG4gICAgICAgICAgICB0aGlzLmNvbnRyb2xsZXIuYWJvcnQoYWJvcnRFcnJvcik7XG4gICAgICAgICAgICB0aGlzLmNvbnRyb2xsZXIgPSB1bmRlZmluZWQ7XG4gICAgICAgIH1cbiAgICB9XG59XG4vKipcbiAqIEEgc2VydmljZSBzaW5nbGV0b24gdG8gaGVscCBlbnN1cmUgdGhhdCBvbmx5IGEgc2luZ2xlIFdlYkF1dGhuIGNlcmVtb255IGlzIGFjdGl2ZSBhdCBhIHRpbWUuXG4gKlxuICogVXNlcnMgb2YgKipAc2ltcGxld2ViYXV0aG4vYnJvd3NlcioqIHNob3VsZG4ndCB0eXBpY2FsbHkgbmVlZCB0byB1c2UgdGhpcywgYnV0IGl0IGNhbiBoZWxwIGUuZy5cbiAqIGRldmVsb3BlcnMgYnVpbGRpbmcgcHJvamVjdHMgdGhhdCB1c2UgY2xpZW50LXNpZGUgcm91dGluZyB0byBiZXR0ZXIgY29udHJvbCB0aGUgYmVoYXZpb3Igb2ZcbiAqIHRoZWlyIFVYIGluIHJlc3BvbnNlIHRvIHJvdXRlciBuYXZpZ2F0aW9uIGV2ZW50cy5cbiAqL1xuZXhwb3J0IGNvbnN0IFdlYkF1dGhuQWJvcnRTZXJ2aWNlID0gbmV3IEJhc2VXZWJBdXRobkFib3J0U2VydmljZSgpO1xuIiwgImNvbnN0IGF0dGFjaG1lbnRzID0gWydjcm9zcy1wbGF0Zm9ybScsICdwbGF0Zm9ybSddO1xuLyoqXG4gKiBJZiBwb3NzaWJsZSBjb2VyY2UgYSBgc3RyaW5nYCB2YWx1ZSBpbnRvIGEga25vd24gYEF1dGhlbnRpY2F0b3JBdHRhY2htZW50YFxuICovXG5leHBvcnQgZnVuY3Rpb24gdG9BdXRoZW50aWNhdG9yQXR0YWNobWVudChhdHRhY2htZW50KSB7XG4gICAgaWYgKCFhdHRhY2htZW50KSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgaWYgKGF0dGFjaG1lbnRzLmluZGV4T2YoYXR0YWNobWVudCkgPCAwKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgcmV0dXJuIGF0dGFjaG1lbnQ7XG59XG4iLCAiaW1wb3J0IHsgYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcgfSBmcm9tICcuLi9oZWxwZXJzL2J1ZmZlclRvQmFzZTY0VVJMU3RyaW5nLmpzJztcbmltcG9ydCB7IGJhc2U2NFVSTFN0cmluZ1RvQnVmZmVyIH0gZnJvbSAnLi4vaGVscGVycy9iYXNlNjRVUkxTdHJpbmdUb0J1ZmZlci5qcyc7XG5pbXBvcnQgeyBicm93c2VyU3VwcG9ydHNXZWJBdXRobiB9IGZyb20gJy4uL2hlbHBlcnMvYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4uanMnO1xuaW1wb3J0IHsgdG9QdWJsaWNLZXlDcmVkZW50aWFsRGVzY3JpcHRvciB9IGZyb20gJy4uL2hlbHBlcnMvdG9QdWJsaWNLZXlDcmVkZW50aWFsRGVzY3JpcHRvci5qcyc7XG5pbXBvcnQgeyBpZGVudGlmeVJlZ2lzdHJhdGlvbkVycm9yIH0gZnJvbSAnLi4vaGVscGVycy9pZGVudGlmeVJlZ2lzdHJhdGlvbkVycm9yLmpzJztcbmltcG9ydCB7IFdlYkF1dGhuQWJvcnRTZXJ2aWNlIH0gZnJvbSAnLi4vaGVscGVycy93ZWJBdXRobkFib3J0U2VydmljZS5qcyc7XG5pbXBvcnQgeyB0b0F1dGhlbnRpY2F0b3JBdHRhY2htZW50IH0gZnJvbSAnLi4vaGVscGVycy90b0F1dGhlbnRpY2F0b3JBdHRhY2htZW50LmpzJztcbi8qKlxuICogQmVnaW4gYXV0aGVudGljYXRvciBcInJlZ2lzdHJhdGlvblwiIHZpYSBXZWJBdXRobiBhdHRlc3RhdGlvblxuICpcbiAqIEBwYXJhbSBvcHRpb25zSlNPTiBPdXRwdXQgZnJvbSAqKkBzaW1wbGV3ZWJhdXRobi9zZXJ2ZXIqKidzIGBnZW5lcmF0ZVJlZ2lzdHJhdGlvbk9wdGlvbnMoKWBcbiAqIEBwYXJhbSB1c2VBdXRvUmVnaXN0ZXIgKE9wdGlvbmFsKSBUcnkgdG8gc2lsZW50bHkgY3JlYXRlIGEgcGFzc2tleSB3aXRoIHRoZSBwYXNzd29yZCBtYW5hZ2VyIHRoYXQgdGhlIHVzZXIganVzdCBzaWduZWQgaW4gd2l0aC4gRGVmYXVsdHMgdG8gYGZhbHNlYC5cbiAqL1xuZXhwb3J0IGFzeW5jIGZ1bmN0aW9uIHN0YXJ0UmVnaXN0cmF0aW9uKG9wdGlvbnMpIHtcbiAgICAvLyBAdHMtaWdub3JlOiBJbnRlbnRpb25hbGx5IGNoZWNrIGZvciBvbGQgY2FsbCBzdHJ1Y3R1cmUgdG8gd2FybiBhYm91dCBpbXByb3BlciBBUEkgY2FsbFxuICAgIGlmICghb3B0aW9ucy5vcHRpb25zSlNPTiAmJiBvcHRpb25zLmNoYWxsZW5nZSkge1xuICAgICAgICBjb25zb2xlLndhcm4oJ3N0YXJ0UmVnaXN0cmF0aW9uKCkgd2FzIG5vdCBjYWxsZWQgY29ycmVjdGx5LiBJdCB3aWxsIHRyeSB0byBjb250aW51ZSB3aXRoIHRoZSBwcm92aWRlZCBvcHRpb25zLCBidXQgdGhpcyBjYWxsIHNob3VsZCBiZSByZWZhY3RvcmVkIHRvIHVzZSB0aGUgZXhwZWN0ZWQgY2FsbCBzdHJ1Y3R1cmUgaW5zdGVhZC4gU2VlIGh0dHBzOi8vc2ltcGxld2ViYXV0aG4uZGV2L2RvY3MvcGFja2FnZXMvYnJvd3NlciN0eXBlZXJyb3ItY2Fubm90LXJlYWQtcHJvcGVydGllcy1vZi11bmRlZmluZWQtcmVhZGluZy1jaGFsbGVuZ2UgZm9yIG1vcmUgaW5mb3JtYXRpb24uJyk7XG4gICAgICAgIC8vIEB0cy1pZ25vcmU6IFJlYXNzaWduIHRoZSBvcHRpb25zLCBwYXNzZWQgaW4gYXMgYSBwb3NpdGlvbmFsIGFyZ3VtZW50LCB0byB0aGUgZXhwZWN0ZWQgdmFyaWFibGVcbiAgICAgICAgb3B0aW9ucyA9IHsgb3B0aW9uc0pTT046IG9wdGlvbnMgfTtcbiAgICB9XG4gICAgY29uc3QgeyBvcHRpb25zSlNPTiwgdXNlQXV0b1JlZ2lzdGVyID0gZmFsc2UgfSA9IG9wdGlvbnM7XG4gICAgaWYgKCFicm93c2VyU3VwcG9ydHNXZWJBdXRobigpKSB7XG4gICAgICAgIHRocm93IG5ldyBFcnJvcignV2ViQXV0aG4gaXMgbm90IHN1cHBvcnRlZCBpbiB0aGlzIGJyb3dzZXInKTtcbiAgICB9XG4gICAgLy8gV2UgbmVlZCB0byBjb252ZXJ0IHNvbWUgdmFsdWVzIHRvIFVpbnQ4QXJyYXlzIGJlZm9yZSBwYXNzaW5nIHRoZSBjcmVkZW50aWFscyB0byB0aGUgbmF2aWdhdG9yXG4gICAgY29uc3QgcHVibGljS2V5ID0ge1xuICAgICAgICAuLi5vcHRpb25zSlNPTixcbiAgICAgICAgY2hhbGxlbmdlOiBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlcihvcHRpb25zSlNPTi5jaGFsbGVuZ2UpLFxuICAgICAgICB1c2VyOiB7XG4gICAgICAgICAgICAuLi5vcHRpb25zSlNPTi51c2VyLFxuICAgICAgICAgICAgaWQ6IGJhc2U2NFVSTFN0cmluZ1RvQnVmZmVyKG9wdGlvbnNKU09OLnVzZXIuaWQpLFxuICAgICAgICB9LFxuICAgICAgICBleGNsdWRlQ3JlZGVudGlhbHM6IG9wdGlvbnNKU09OLmV4Y2x1ZGVDcmVkZW50aWFscz8ubWFwKHRvUHVibGljS2V5Q3JlZGVudGlhbERlc2NyaXB0b3IpLFxuICAgIH07XG4gICAgLy8gUHJlcGFyZSBvcHRpb25zIGZvciBgLmNyZWF0ZSgpYFxuICAgIGNvbnN0IGNyZWF0ZU9wdGlvbnMgPSB7fTtcbiAgICAvKipcbiAgICAgKiBUcnkgdG8gdXNlIGNvbmRpdGlvbmFsIGNyZWF0ZSB0byByZWdpc3RlciBhIHBhc3NrZXkgZm9yIHRoZSB1c2VyIHdpdGggdGhlIHBhc3N3b3JkIG1hbmFnZXJcbiAgICAgKiB0aGUgdXNlciBqdXN0IHVzZWQgdG8gYXV0aGVudGljYXRlIHdpdGguIFRoZSB1c2VyIHdvbid0IGJlIHNob3duIGFueSBwcm9taW5lbnQgVUkgYnkgdGhlXG4gICAgICogYnJvd3Nlci5cbiAgICAgKi9cbiAgICBpZiAodXNlQXV0b1JlZ2lzdGVyKSB7XG4gICAgICAgIC8vIEB0cy1pZ25vcmU6IGBtZWRpYXRpb25gIGRvZXNuJ3QgeWV0IGV4aXN0IG9uIENyZWRlbnRpYWxDcmVhdGlvbk9wdGlvbnMgYnV0IGl0J3MgcG9zc2libGUgYXMgb2YgU2VwdCAyMDI0XG4gICAgICAgIGNyZWF0ZU9wdGlvbnMubWVkaWF0aW9uID0gJ2NvbmRpdGlvbmFsJztcbiAgICB9XG4gICAgLy8gRmluYWxpemUgb3B0aW9uc1xuICAgIGNyZWF0ZU9wdGlvbnMucHVibGljS2V5ID0gcHVibGljS2V5O1xuICAgIC8vIFNldCB1cCB0aGUgYWJpbGl0eSB0byBjYW5jZWwgdGhpcyByZXF1ZXN0IGlmIHRoZSB1c2VyIGF0dGVtcHRzIGFub3RoZXJcbiAgICBjcmVhdGVPcHRpb25zLnNpZ25hbCA9IFdlYkF1dGhuQWJvcnRTZXJ2aWNlLmNyZWF0ZU5ld0Fib3J0U2lnbmFsKCk7XG4gICAgLy8gV2FpdCBmb3IgdGhlIHVzZXIgdG8gY29tcGxldGUgYXR0ZXN0YXRpb25cbiAgICBsZXQgY3JlZGVudGlhbDtcbiAgICB0cnkge1xuICAgICAgICBjcmVkZW50aWFsID0gKGF3YWl0IG5hdmlnYXRvci5jcmVkZW50aWFscy5jcmVhdGUoY3JlYXRlT3B0aW9ucykpO1xuICAgIH1cbiAgICBjYXRjaCAoZXJyKSB7XG4gICAgICAgIHRocm93IGlkZW50aWZ5UmVnaXN0cmF0aW9uRXJyb3IoeyBlcnJvcjogZXJyLCBvcHRpb25zOiBjcmVhdGVPcHRpb25zIH0pO1xuICAgIH1cbiAgICBpZiAoIWNyZWRlbnRpYWwpIHtcbiAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdSZWdpc3RyYXRpb24gd2FzIG5vdCBjb21wbGV0ZWQnKTtcbiAgICB9XG4gICAgY29uc3QgeyBpZCwgcmF3SWQsIHJlc3BvbnNlLCB0eXBlIH0gPSBjcmVkZW50aWFsO1xuICAgIC8vIENvbnRpbnVlIHRvIHBsYXkgaXQgc2FmZSB3aXRoIGBnZXRUcmFuc3BvcnRzKClgIGZvciBub3csIGV2ZW4gd2hlbiBMMyB0eXBlcyBzYXkgaXQncyByZXF1aXJlZFxuICAgIGxldCB0cmFuc3BvcnRzID0gdW5kZWZpbmVkO1xuICAgIGlmICh0eXBlb2YgcmVzcG9uc2UuZ2V0VHJhbnNwb3J0cyA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICB0cmFuc3BvcnRzID0gcmVzcG9uc2UuZ2V0VHJhbnNwb3J0cygpO1xuICAgIH1cbiAgICAvLyBMMyBzYXlzIHRoaXMgaXMgcmVxdWlyZWQsIGJ1dCBicm93c2VyIGFuZCB3ZWJ2aWV3IHN1cHBvcnQgYXJlIHN0aWxsIG5vdCBndWFyYW50ZWVkLlxuICAgIGxldCByZXNwb25zZVB1YmxpY0tleUFsZ29yaXRobSA9IHVuZGVmaW5lZDtcbiAgICBpZiAodHlwZW9mIHJlc3BvbnNlLmdldFB1YmxpY0tleUFsZ29yaXRobSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgcmVzcG9uc2VQdWJsaWNLZXlBbGdvcml0aG0gPSByZXNwb25zZS5nZXRQdWJsaWNLZXlBbGdvcml0aG0oKTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHdhcm5PbkJyb2tlbkltcGxlbWVudGF0aW9uKCdnZXRQdWJsaWNLZXlBbGdvcml0aG0oKScsIGVycm9yKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBsZXQgcmVzcG9uc2VQdWJsaWNLZXkgPSB1bmRlZmluZWQ7XG4gICAgaWYgKHR5cGVvZiByZXNwb25zZS5nZXRQdWJsaWNLZXkgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIGNvbnN0IF9wdWJsaWNLZXkgPSByZXNwb25zZS5nZXRQdWJsaWNLZXkoKTtcbiAgICAgICAgICAgIGlmIChfcHVibGljS2V5ICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgcmVzcG9uc2VQdWJsaWNLZXkgPSBidWZmZXJUb0Jhc2U2NFVSTFN0cmluZyhfcHVibGljS2V5KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHdhcm5PbkJyb2tlbkltcGxlbWVudGF0aW9uKCdnZXRQdWJsaWNLZXkoKScsIGVycm9yKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICAvLyBMMyBzYXlzIHRoaXMgaXMgcmVxdWlyZWQsIGJ1dCBicm93c2VyIGFuZCB3ZWJ2aWV3IHN1cHBvcnQgYXJlIHN0aWxsIG5vdCBndWFyYW50ZWVkLlxuICAgIGxldCByZXNwb25zZUF1dGhlbnRpY2F0b3JEYXRhO1xuICAgIGlmICh0eXBlb2YgcmVzcG9uc2UuZ2V0QXV0aGVudGljYXRvckRhdGEgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIHJlc3BvbnNlQXV0aGVudGljYXRvckRhdGEgPSBidWZmZXJUb0Jhc2U2NFVSTFN0cmluZyhyZXNwb25zZS5nZXRBdXRoZW50aWNhdG9yRGF0YSgpKTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHdhcm5PbkJyb2tlbkltcGxlbWVudGF0aW9uKCdnZXRBdXRoZW50aWNhdG9yRGF0YSgpJywgZXJyb3IpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJldHVybiB7XG4gICAgICAgIGlkLFxuICAgICAgICByYXdJZDogYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcocmF3SWQpLFxuICAgICAgICByZXNwb25zZToge1xuICAgICAgICAgICAgYXR0ZXN0YXRpb25PYmplY3Q6IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKHJlc3BvbnNlLmF0dGVzdGF0aW9uT2JqZWN0KSxcbiAgICAgICAgICAgIGNsaWVudERhdGFKU09OOiBidWZmZXJUb0Jhc2U2NFVSTFN0cmluZyhyZXNwb25zZS5jbGllbnREYXRhSlNPTiksXG4gICAgICAgICAgICB0cmFuc3BvcnRzLFxuICAgICAgICAgICAgcHVibGljS2V5QWxnb3JpdGhtOiByZXNwb25zZVB1YmxpY0tleUFsZ29yaXRobSxcbiAgICAgICAgICAgIHB1YmxpY0tleTogcmVzcG9uc2VQdWJsaWNLZXksXG4gICAgICAgICAgICBhdXRoZW50aWNhdG9yRGF0YTogcmVzcG9uc2VBdXRoZW50aWNhdG9yRGF0YSxcbiAgICAgICAgfSxcbiAgICAgICAgdHlwZSxcbiAgICAgICAgY2xpZW50RXh0ZW5zaW9uUmVzdWx0czogY3JlZGVudGlhbC5nZXRDbGllbnRFeHRlbnNpb25SZXN1bHRzKCksXG4gICAgICAgIGF1dGhlbnRpY2F0b3JBdHRhY2htZW50OiB0b0F1dGhlbnRpY2F0b3JBdHRhY2htZW50KGNyZWRlbnRpYWwuYXV0aGVudGljYXRvckF0dGFjaG1lbnQpLFxuICAgIH07XG59XG4vKipcbiAqIFZpc2libHkgd2FybiB3aGVuIHdlIGRldGVjdCBhbiBpc3N1ZSByZWxhdGVkIHRvIGEgcGFzc2tleSBwcm92aWRlciBpbnRlcmNlcHRpbmcgV2ViQXV0aG4gQVBJXG4gKiBjYWxsc1xuICovXG5mdW5jdGlvbiB3YXJuT25Ccm9rZW5JbXBsZW1lbnRhdGlvbihtZXRob2ROYW1lLCBjYXVzZSkge1xuICAgIGNvbnNvbGUud2FybihgVGhlIGJyb3dzZXIgZXh0ZW5zaW9uIHRoYXQgaW50ZXJjZXB0ZWQgdGhpcyBXZWJBdXRobiBBUEkgY2FsbCBpbmNvcnJlY3RseSBpbXBsZW1lbnRlZCAke21ldGhvZE5hbWV9LiBZb3Ugc2hvdWxkIHJlcG9ydCB0aGlzIGVycm9yIHRvIHRoZW0uXFxuYCwgY2F1c2UpO1xufVxuIiwgImltcG9ydCB7XG4gICAgaXNGdW5jdGlvbixcbn0gZnJvbSAnLi4vdXRpbHMuanMnO1xuXG5pbXBvcnQgbWl4aW4gZnJvbSAnLi9taXhpbic7XG5cbmltcG9ydCB7XG4gICAgYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4sXG4gICAgc3RhcnRSZWdpc3RyYXRpb24sXG59IGZyb20gJ0BzaW1wbGV3ZWJhdXRobi9icm93c2VyJztcblxuY29uc3QgcmVnaXN0ZXJXZWJhdXRobiA9ICh7XG4gICAgYmVmb3JlID0gdW5kZWZpbmVkLFxuICAgIHJlZ2lzdGVyRGF0YSA9IHt9LFxuICAgIHJlZ2lzdGVyVXJsID0gdW5kZWZpbmVkLFxuICAgIHB1YmxpY0tleSA9IHVuZGVmaW5lZCxcbiAgICB2ZXJpZnlLZXlNZXRob2QgPSAndmVyaWZ5S2V5Jyxcbn0pID0+ICh7XG4gICAgYmVmb3JlLFxuICAgIHJlZ2lzdGVyRGF0YSxcbiAgICByZWdpc3RlclVybCxcbiAgICBwdWJsaWNLZXksXG4gICAgdmVyaWZ5S2V5TWV0aG9kLFxuICAgIGVycm9yOiBudWxsLFxuICAgIHByb2Nlc3Npbmc6IGZhbHNlLFxuICAgIGJyb3dzZXJTdXBwb3J0c1dlYkF1dGhuLFxuICAgIC4uLm1peGluLFxuXG4gICAgYXN5bmMgcmVnaXN0ZXIoKSB7XG4gICAgICAgIHRoaXMuZXJyb3IgPSBudWxsO1xuXG4gICAgICAgIGlmICghIHRoaXMuYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4oKSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKGlzRnVuY3Rpb24odGhpcy5iZWZvcmUpKSB7XG4gICAgICAgICAgICBjb25zdCBjYWxsYmFjayA9IHRoaXMuYmVmb3JlLmJpbmQodGhpcyk7XG4gICAgICAgICAgICBjb25zdCBpc1ZhbGlkID0gYXdhaXQgY2FsbGJhY2soKTtcblxuICAgICAgICAgICAgaWYgKCEgaXNWYWxpZCkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIGxldCBwdWJsaWNLZXkgPSB0aGlzLnB1YmxpY0tleTtcbiAgICAgICAgdGhpcy5wcm9jZXNzaW5nID0gdHJ1ZTtcblxuICAgICAgICBjb25zdCByZWdpc3RlckRhdGEgPSBpc0Z1bmN0aW9uKHRoaXMucmVnaXN0ZXJEYXRhKVxuICAgICAgICAgICAgPyB0aGlzLnJlZ2lzdGVyRGF0YSgpXG4gICAgICAgICAgICA6IHRoaXMucmVnaXN0ZXJEYXRhO1xuXG4gICAgICAgIGlmICh0aGlzLnJlZ2lzdGVyVXJsKSB7XG4gICAgICAgICAgICBjb25zdCByZXNwb25zZSA9IGF3YWl0IGZldGNoKHRoaXMucmVnaXN0ZXJVcmwsIHRoaXMuX2FqYXhPcHRpb25zKHJlZ2lzdGVyRGF0YSkpO1xuXG4gICAgICAgICAgICBpZiAoISByZXNwb25zZS5vaykge1xuICAgICAgICAgICAgICAgIHRoaXMucHJvY2Vzc2luZyA9IGZhbHNlO1xuXG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMubm90aWZ5UHVibGljS2V5RXJyb3IoKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgcHVibGljS2V5ID0gYXdhaXQgcmVzcG9uc2UuanNvbigpO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKCEgdGhpcy5pc1ZhbGlkUHVibGljS2V5KHB1YmxpY0tleSwgJ3JwJykpIHtcbiAgICAgICAgICAgIHRoaXMucHJvY2Vzc2luZyA9IGZhbHNlO1xuXG4gICAgICAgICAgICByZXR1cm4gdGhpcy5ub3RpZnlQdWJsaWNLZXlFcnJvcigpO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIHN0YXJ0UmVnaXN0cmF0aW9uKHsgb3B0aW9uc0pTT046IHB1YmxpY0tleSB9KVxuICAgICAgICAgICAgLnRoZW4ocmVzcCA9PiB0aGlzLiR3aXJlLmNhbGwodGhpcy52ZXJpZnlLZXlNZXRob2QsIHJlc3ApKVxuICAgICAgICAgICAgLmNhdGNoKGVycm9yID0+IHRoaXMuZXJyb3IgPSBlcnJvcj8ucmVzcG9uc2U/LmRhdGE/Lm1lc3NhZ2UgPz8gZXJyb3IpXG4gICAgICAgICAgICAuZmluYWxseSgoKSA9PiB0aGlzLnByb2Nlc3NpbmcgPSBmYWxzZSk7XG4gICAgfSxcbn0pO1xuXG5leHBvcnQgZGVmYXVsdCByZWdpc3RlcldlYmF1dGhuO1xuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUNPLElBQU0sZUFBZSxNQUFNO0FBQzlCLE1BQUksU0FBUyxjQUFjLHlCQUF5QixHQUFHO0FBQ25ELFdBQU8sU0FBUyxjQUFjLHlCQUF5QixFQUFFLGFBQWEsU0FBUztBQUFBLEVBQ25GO0FBRUEsTUFBSSxTQUFTLGNBQWMsYUFBYSxHQUFHO0FBQ3ZDLFdBQU8sU0FBUyxjQUFjLGFBQWEsRUFBRSxhQUFhLFdBQVc7QUFBQSxFQUN6RTtB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QSxJQUM3RCx5QkFBeUIsMEJBQTBCLFdBQVcsdUJBQXVCO0FBQUEsRUFDekY7QUFDSjtBQUtBLFNBQVMsMkJBQTJCLFlBQVksT0FBTztBQUNuRCxVQUFRLEtBQUsseUZBQXlGLFVBQVU7QUFBQSxHQUE2QyxLQUFLO0FBQ3RLOzs7QUM3R0EsSUFBTSxtQkFBbUIsQ0FBQztBQUFBLEVBQ3RCLFNBQVM7QUFBQSxFQUNULGVBQWUsQ0FBQztBQUFBLEVBQ2hCLGNBQWM7QUFBQSxFQUNkLFlBQVk7QUFBQSxFQUNaLGtCQUFrQjtBQUN0QixPQUFPO0FBQUEsRUFDSDtBQUFBLEVBQ0E7QUFBQSxFQUNBO0FBQUEsRUFDQTtBQUFBLEVBQ0E7QUFBQSxFQUNBLE9BQU87QUFBQSxFQUNQLFlBQVk7QUFBQSxFQUNaO0FBQUEsRUFDQSxHQUFHO0FBQUEsRUFFSCxNQUFNLFdBQVc7QUFDYixTQUFLLFFBQVE7QUFFYixRQUFJLENBQUUsS0FBSyx3QkFBd0IsR0FBRztBQUNsQztBQUFBLElBQ0o7QUFFQSxRQUFJLFdBQVcsS0FBSyxNQUFNLEdBQUc7QUFDekIsWUFBTSxXQUFXLEtBQUssT0FBTyxLQUFLLElBQUk7QUFDdEMsWUFBTSxVQUFVLE1BQU0sU0FBUztBQUUvQixVQUFJLENBQUUsU0FBUztBQUNYO0FBQUEsTUFDSjtBQUFBLElBQ0o7QUFFQSxRQUFJQSxhQUFZLEtBQUs7QUFDckIsU0FBSyxhQUFhO0FBRWxCLFVBQU1DLGdCQUFlLFdBQVcsS0FBSyxZQUFZLElBQzNDLEtBQUssYUFBYSxJQUNsQixLQUFLO0FBRVgsUUFBSSxLQUFLLGFBQWE7QUFDbEIsWUFBTSxXQUFXLE1BQU0sTUFBTSxLQUFLLGFBQWEsS0FBSyxhQUFhQSxhQUFZLENBQUM7QUFFOUUsVUFBSSxDQUFFLFNBQVMsSUFBSTtBQUNmLGFBQUssYUFBYTtBQUVsQixlQUFPLEtBQUsscUJBQXFCO0FBQUEsTUFDckM7QUFFQSxNQUFBRCxhQUFZLE1BQU0sU0FBUyxLQUFLO0FBQUEsSUFDcEM7QUFFQSxRQUFJLENBQUUsS0FBSyxpQkFBaUJBLFlBQVcsSUFBSSxHQUFHO0FBQzFDLFdBQUssYUFBYTtBQUVsQixhQUFPLEtBQUsscUJBQXFCO0FBQUEsSUFDckM7QUFFQSxXQUFPLGtCQUFrQixFQUFFLGFBQWFBLFdBQVUsQ0FBQyxFQUM5QyxLQUFLLFVBQVEsS0FBSyxNQUFNLEtBQUssS0FBSyxpQkFBaUIsSUFBSSxDQUFDLEVBQ3hELE1BQU0sV0FBUyxLQUFLLFFBQVEsT0FBTyxVQUFVLE1BQU0sV0FBVyxLQUFLLEVBQ25FLFFBQVEsTUFBTSxLQUFLLGFBQWEsS0FBSztBQUFBLEVBQzlDO0FBQ0o7QUFFQSxJQUFPLG1CQUFROyIsCiAgIm5hbWVzIjogWyJwdWJsaWNLZXkiLCAicmVnaXN0ZXJEYXRhIl0KfQo=
