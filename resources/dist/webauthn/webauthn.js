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

// node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js
function browserSupportsWebAuthnAutofill() {
  if (!browserSupportsWebAuthn()) {
    return _browserSupportsWebAuthnAutofillInternals.stubThis(new Promise((resolve) => resolve(false)));
  }
  const globalPublicKeyCredential = globalThis.PublicKeyCredential;
  if (globalPublicKeyCredential?.isConditionalMediationAvailable === void 0) {
    return _browserSupportsWebAuthnAutofillInternals.stubThis(new Promise((resolve) => resolve(false)));
  }
  return _browserSupportsWebAuthnAutofillInternals.stubThis(globalPublicKeyCredential.isConditionalMediationAvailable());
}
var _browserSupportsWebAuthnAutofillInternals = {
  stubThis: (value) => value
};

// node_modules/@simplewebauthn/browser/esm/helpers/identifyAuthenticationError.js
function identifyAuthenticationError({ error, options }) {
  const { publicKey } = options;
  if (!publicKey) {
    throw Error("options was missing required publicKey property");
  }
  if (error.name === "AbortError") {
    if (options.signal instanceof AbortSignal) {
      return new WebAuthnError({
        message: "Authentication ceremony was sent an abort signal",
        code: "ERROR_CEREMONY_ABORTED",
        cause: error
      });
    }
  } else if (error.name === "NotAllowedError") {
    return new WebAuthnError({
      message: error.message,
      code: "ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY",
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
    } else if (publicKey.rpId !== effectiveDomain) {
      return new WebAuthnError({
        message: `The RP ID "${publicKey.rpId}" is invalid for this domain`,
        code: "ERROR_INVALID_RP_ID",
        cause: error
      });
    }
  } else if (error.name === "UnknownError") {
    return new WebAuthnError({
      message: "The authenticator was unable to process the specified options, or could not create a new assertion signature",
      code: "ERROR_AUTHENTICATOR_GENERAL_ERROR",
      cause: error
    });
  }
  return error;
}

// node_modules/@simplewebauthn/browser/esm/methods/startAuthentication.js
async function startAuthentication(options) {
  if (!options.optionsJSON && options.challenge) {
    console.warn("startAuthentication() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.");
    options = { optionsJSON: options };
  }
  const { optionsJSON, useBrowserAutofill = false, verifyBrowserAutofillInput = true } = options;
  if (!browserSupportsWebAuthn()) {
    throw new Error("WebAuthn is not supported in this browser");
  }
  let allowCredentials;
  if (optionsJSON.allowCredentials?.length !== 0) {
    allowCredentials = optionsJSON.allowCredentials?.map(toPublicKeyCredentialDescriptor);
  }
  const publicKey = {
    ...optionsJSON,
    challenge: base64URLStringToBuffer(optionsJSON.challenge),
    allowCredentials
  };
  const getOptions = {};
  if (useBrowserAutofill) {
    if (!await browserSupportsWebAuthnAutofill()) {
      throw Error("Browser does not support WebAuthn autofill");
    }
    const eligibleInputs = document.querySelectorAll("input[autocomplete$='webauthn']");
    if (eligibleInputs.length < 1 && verifyBrowserAutofillInput) {
      throw Error('No <input> with "webauthn" as the only or last value in its `autocomplete` attribute was detected');
    }
    getOptions.mediation = "conditional";
    publicKey.allowCredentials = [];
  }
  getOptions.publicKey = publicKey;
  getOptions.signal = WebAuthnAbortService.createNewAbortSignal();
  let credential;
  try {
    credential = await navigator.credentials.get(getOptions);
  } catch (err) {
    throw identifyAuthenticationError({ error: err, options: getOptions });
  }
  if (!credential) {
    throw new Error("Authentication was not completed");
  }
  const { id, rawId, response, type } = credential;
  let userHandle = void 0;
  if (response.userHandle) {
    userHandle = bufferToBase64URLString(response.userHandle);
  }
  return {
    id,
    rawId: bufferToBase64URLString(rawId),
    response: {
      authenticatorData: bufferToBase64URLString(response.authenticatorData),
      clientDataJSON: bufferToBase64URLString(response.clientDataJSON),
      signature: bufferToBase64URLString(response.signature),
      userHandle
    },
    type,
    clientExtensionResults: credential.getClientExtensionResults(),
    authenticatorAttachment: toAuthenticatorAttachment(credential.authenticatorAttachment)
  };
}

// resources/js/webauthn/index.js
window.browserSupportsWebAuthn = browserSupportsWebAuthn;
window.startAuthentication = startAuthentication;
window.startRegistration = startRegistration;
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL2J1ZmZlclRvQmFzZTY0VVJMU3RyaW5nLmpzIiwgIi4uLy4uLy4uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vaGVscGVycy9iYXNlNjRVUkxTdHJpbmdUb0J1ZmZlci5qcyIsICIuLi8uLi8uLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL2hlbHBlcnMvYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4uanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL3RvUHVibGljS2V5Q3JlZGVudGlhbERlc2NyaXB0b3IuanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL2lzVmFsaWREb21haW4uanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL3dlYkF1dGhuRXJyb3IuanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL2lkZW50aWZ5UmVnaXN0cmF0aW9uRXJyb3IuanMiLCAiLi4vLi4vLi4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL3dlYkF1dGhuQWJvcnRTZXJ2aWNlLmpzIiwgIi4uLy4uLy4uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vaGVscGVycy90b0F1dGhlbnRpY2F0b3JBdHRhY2htZW50LmpzIiwgIi4uLy4uLy4uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vbWV0aG9kcy9zdGFydFJlZ2lzdHJhdGlvbi5qcyIsICIuLi8uLi8uLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL2hlbHBlcnMvYnJvd3NlclN1cHBvcnRzV2ViQXV0aG5BdXRvZmlsbC5qcyIsICIuLi8uLi8uLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL2hlbHBlcnMvaWRlbnRpZnlBdXRoZW50aWNhdGlvbkVycm9yLmpzIiwgIi4uLy4uLy4uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vbWV0aG9kcy9zdGFydEF1dGhlbnRpY2F0aW9uLmpzIiwgIi4uLy4uL2pzL3dlYmF1dGhuL2luZGV4LmpzIl0sCiAgInNvdXJjZXNDb250ZW50IjogWyIvKipcbiAqIENvbnZlcnQgdGhlIGdpdmVuIGFycmF5IGJ1ZmZlciBpbnRvIGEgQmFzZTY0VVJMLWVuY29kZWQgc3RyaW5nLiBJZGVhbCBmb3IgY29udmVydGluZyB2YXJpb3VzXG4gKiBjcmVkZW50aWFsIHJlc3BvbnNlIEFycmF5QnVmZmVycyB0byBzdHJpbmcgZm9yIHNlbmRpbmcgYmFjayB0byB0aGUgc2VydmVyIGFzIEpTT04uXG4gKlxuICogSGVscGVyIG1ldGhvZCB0byBjb21wbGltZW50IGBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlcmBcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKGJ1ZmZlcikge1xuICAgIGNvbnN0IGJ5dGVzID0gbmV3IFVpbnQ4QXJyYXkoYnVmZmVyKTtcbiAgICBsZXQgc3RyID0gJyc7XG4gICAgZm9yIChjb25zdCBjaGFyQ29kZSBvZiBieXRlcykge1xuICAgICAgICBzdHIgKz0gU3RyaW5nLmZyb21DaGFyQ29kZShjaGFyQ29kZSk7XG4gICAgfVxuICAgIGNvbnN0IGJhc2U2NFN0cmluZyA9IGJ0b2Eoc3RyKTtcbiAgICByZXR1cm4gYmFzZTY0U3RyaW5nLnJlcGxhY2UoL1xcKy9nLCAnLScpLnJlcGxhY2UoL1xcLy9nLCAnXycpLnJlcGxhY2UoLz0vZywgJycpO1xufVxuIiwgIi8qKlxuICogQ29udmVydCBmcm9tIGEgQmFzZTY0VVJMLWVuY29kZWQgc3RyaW5nIHRvIGFuIEFycmF5IEJ1ZmZlci4gQmVzdCB1c2VkIHdoZW4gY29udmVydGluZyBhXG4gKiBjcmVkZW50aWFsIElEIGZyb20gYSBKU09OIHN0cmluZyB0byBhbiBBcnJheUJ1ZmZlciwgbGlrZSBpbiBhbGxvd0NyZWRlbnRpYWxzIG9yXG4gKiBleGNsdWRlQ3JlZGVudGlhbHNcbiAqXG4gKiBIZWxwZXIgbWV0aG9kIHRvIGNvbXBsaW1lbnQgYGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nYFxuICovXG5leHBvcnQgZnVuY3Rpb24gYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIoYmFzZTY0VVJMU3RyaW5nKSB7XG4gICAgLy8gQ29udmVydCBmcm9tIEJhc2U2NFVSTCB0byBCYXNlNjRcbiAgICBjb25zdCBiYXNlNjQgPSBiYXNlNjRVUkxTdHJpbmcucmVwbGFjZSgvLS9nLCAnKycpLnJlcGxhY2UoL18vZywgJy8nKTtcbiAgICAvKipcbiAgICAgKiBQYWQgd2l0aCAnPScgdW50aWwgaXQncyBhIG11bHRpcGxlIG9mIGZvdXJcbiAgICAgKiAoNCAtICg4NSAlIDQgPSAxKSA9IDMpICUgNCA9IDMgcGFkZGluZ1xuICAgICAqICg0IC0gKDg2ICUgNCA9IDIpID0gMikgJSA0ID0gMiBwYWRkaW5nXG4gICAgICogKDQgLSAoODcgJSA0ID0gMykgPSAxKSAlIDQgPSAxIHBhZGRpbmdcbiAgICAgKiAoNCAtICg4OCAlIDQgPSAwKSA9IDQpICUgNCA9IDAgcGFkZGluZ1xuICAgICAqL1xuICAgIGNvbnN0IHBhZExlbmd0aCA9ICg0IC0gKGJhc2U2NC5sZW5ndGggJSA0KSkgJSA0O1xuICAgIGNvbnN0IHBhZGRlZCA9IGJhc2U2NC5wYWRFbmQoYmFzZTY0Lmxlbmd0aCArIHBhZExlbmd0aCwgJz0nKTtcbiAgICAvLyBDb252ZXJ0IHRvIGEgYmluYXJ5IHN0cmluZ1xuICAgIGNvbnN0IGJpbmFyeSA9IGF0b2IocGFkZGVkKTtcbiAgICAvLyBDb252ZXJ0IGJpbmFyeSBzdHJpbmcgdG8gYnVmZmVyXG4gICAgY29uc3QgYnVmZmVyID0gbmV3IEFycmF5QnVmZmVyKGJpbmFyeS5sZW5ndGgpO1xuICAgIGNvbnN0IGJ5dGVzID0gbmV3IFVpbnQ4QXJyYXkoYnVmZmVyKTtcbiAgICBmb3IgKGxldCBpID0gMDsgaSA8IGJpbmFyeS5sZW5ndGg7IGkrKykge1xuICAgICAgICBieXRlc1tpXSA9IGJpbmFyeS5jaGFyQ29kZUF0KGkpO1xuICAgIH1cbiAgICByZXR1cm4gYnVmZmVyO1xufVxuIiwgIi8qKlxuICogRGV0ZXJtaW5lIGlmIHRoZSBicm93c2VyIGlzIGNhcGFibGUgb2YgV2ViYXV0aG5cbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGJyb3dzZXJTdXBwb3J0c1dlYkF1dGhuKCkge1xuICAgIHJldHVybiBfYnJvd3NlclN1cHBvcnRzV2ViQXV0aG5JbnRlcm5hbHMuc3R1YlRoaXMoZ2xvYmFsVGhpcz8uUHVibGljS2V5Q3JlZGVudGlhbCAhPT0gdW5kZWZpbmVkICYmXG4gICAgICAgIHR5cGVvZiBnbG9iYWxUaGlzLlB1YmxpY0tleUNyZWRlbnRpYWwgPT09ICdmdW5jdGlvbicpO1xufVxuLyoqXG4gKiBNYWtlIGl0IHBvc3NpYmxlIHRvIHN0dWIgdGhlIHJldHVybiB2YWx1ZSBkdXJpbmcgdGVzdGluZ1xuICogQGlnbm9yZSBEb24ndCBpbmNsdWRlIHRoaXMgaW4gZG9jcyBvdXRwdXRcbiAqL1xuZXhwb3J0IGNvbnN0IF9icm93c2VyU3VwcG9ydHNXZWJBdXRobkludGVybmFscyA9IHtcbiAgICBzdHViVGhpczogKHZhbHVlKSA9PiB2YWx1ZSxcbn07XG4iLCAiaW1wb3J0IHsgYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIgfSBmcm9tICcuL2Jhc2U2NFVSTFN0cmluZ1RvQnVmZmVyLmpzJztcbmV4cG9ydCBmdW5jdGlvbiB0b1B1YmxpY0tleUNyZWRlbnRpYWxEZXNjcmlwdG9yKGRlc2NyaXB0b3IpIHtcbiAgICBjb25zdCB7IGlkIH0gPSBkZXNjcmlwdG9yO1xuICAgIHJldHVybiB7XG4gICAgICAgIC4uLmRlc2NyaXB0b3IsXG4gICAgICAgIGlkOiBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlcihpZCksXG4gICAgICAgIC8qKlxuICAgICAgICAgKiBgZGVzY3JpcHRvci50cmFuc3BvcnRzYCBpcyBhbiBhcnJheSBvZiBvdXIgYEF1dGhlbnRpY2F0b3JUcmFuc3BvcnRGdXR1cmVgIHRoYXQgaW5jbHVkZXMgbmV3ZXJcbiAgICAgICAgICogdHJhbnNwb3J0cyB0aGF0IFR5cGVTY3JpcHQncyBET00gbGliIGlzIGlnbm9yYW50IG9mLiBDb252aW5jZSBUUyB0aGF0IG91ciBsaXN0IG9mIHRyYW5zcG9ydHNcbiAgICAgICAgICogYXJlIGZpbmUgdG8gcGFzcyB0byBXZWJBdXRobiBzaW5jZSBicm93c2VycyB3aWxsIHJlY29nbml6ZSB0aGUgbmV3IHZhbHVlLlxuICAgICAgICAgKi9cbiAgICAgICAgdHJhbnNwb3J0czogZGVzY3JpcHRvci50cmFuc3BvcnRzLFxuICAgIH07XG59XG4iLCAiLyoqXG4gKiBBIHNpbXBsZSB0ZXN0IHRvIGRldGVybWluZSBpZiBhIGhvc3RuYW1lIGlzIGEgcHJvcGVybHktZm9ybWF0dGVkIGRvbWFpbiBuYW1lXG4gKlxuICogQSBcInZhbGlkIGRvbWFpblwiIGlzIGRlZmluZWQgaGVyZTogaHR0cHM6Ly91cmwuc3BlYy53aGF0d2cub3JnLyN2YWxpZC1kb21haW5cbiAqXG4gKiBSZWdleCB3YXMgb3JpZ2luYWxseSBzb3VyY2VkIGZyb20gaGVyZSwgdGhlbiByZW1peGVkIHRvIGFkZCBwdW55Y29kZSBzdXBwb3J0OlxuICogaHR0cHM6Ly93d3cub3JlaWxseS5jb20vbGlicmFyeS92aWV3L3JlZ3VsYXItZXhwcmVzc2lvbnMtY29va2Jvb2svOTc4MTQ0OTMyNzQ1My9jaDA4czE1Lmh0bWxcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGlzVmFsaWREb21haW4oaG9zdG5hbWUpIHtcbiAgICByZXR1cm4gKFxuICAgIC8vIENvbnNpZGVyIGxvY2FsaG9zdCB2YWxpZCBhcyB3ZWxsIHNpbmNlIGl0J3Mgb2theSB3cnQgU2VjdXJlIENvbnRleHRzXG4gICAgaG9zdG5hbWUgPT09ICdsb2NhbGhvc3QnIHx8XG4gICAgICAgIC8vIFN1cHBvcnQgcHVueWNvZGUgKEFDRSkgb3IgYXNjaWkgbGFiZWxzIGFuZCBkb21haW5zXG4gICAgICAgIC9eKCh4bi0tW2EtejAtOS1dK3xbYS16MC05XSsoLVthLXowLTldKykqKVxcLikrKFthLXpdezIsfXx4bi0tW2EtejAtOS1dKykkL2kudGVzdChob3N0bmFtZSkpO1xufVxuIiwgIi8qKlxuICogQSBjdXN0b20gRXJyb3IgdXNlZCB0byByZXR1cm4gYSBtb3JlIG51YW5jZWQgZXJyb3IgZGV0YWlsaW5nIF93aHlfIG9uZSBvZiB0aGUgZWlnaHQgZG9jdW1lbnRlZFxuICogZXJyb3JzIGluIHRoZSBzcGVjIHdhcyByYWlzZWQgYWZ0ZXIgY2FsbGluZyBgbmF2aWdhdG9yLmNyZWRlbnRpYWxzLmNyZWF0ZSgpYCBvclxuICogYG5hdmlnYXRvci5jcmVkZW50aWFscy5nZXQoKWA6XG4gKlxuICogLSBgQWJvcnRFcnJvcmBcbiAqIC0gYENvbnN0cmFpbnRFcnJvcmBcbiAqIC0gYEludmFsaWRTdGF0ZUVycm9yYFxuICogLSBgTm90QWxsb3dlZEVycm9yYFxuICogLSBgTm90U3VwcG9ydGVkRXJyb3JgXG4gKiAtIGBTZWN1cml0eUVycm9yYFxuICogLSBgVHlwZUVycm9yYFxuICogLSBgVW5rbm93bkVycm9yYFxuICpcbiAqIEVycm9yIG1lc3NhZ2VzIHdlcmUgZGV0ZXJtaW5lZCB0aHJvdWdoIGludmVzdGlnYXRpb24gb2YgdGhlIHNwZWMgdG8gZGV0ZXJtaW5lIHVuZGVyIHdoaWNoXG4gKiBzY2VuYXJpb3MgYSBnaXZlbiBlcnJvciB3b3VsZCBiZSByYWlzZWQuXG4gKi9cbmV4cG9ydCBjbGFzcyBXZWJBdXRobkVycm9yIGV4dGVuZHMgRXJyb3Ige1xuICAgIGNvbnN0cnVjdG9yKHsgbWVzc2FnZSwgY29kZSwgY2F1c2UsIG5hbWUsIH0pIHtcbiAgICAgICAgLy8gQHRzLWlnbm9yZTogaGVscCBSb2xsdXAgdW5kZXJzdGFuZCB0aGF0IGBjYXVzZWAgaXMgb2theSB0byBzZXRcbiAgICAgICAgc3VwZXIobWVzc2FnZSwgeyBjYXVzZSB9KTtcbiAgICAgICAgT2JqZWN0LmRlZmluZVByb3BlcnR5KHRoaXMsIFwiY29kZVwiLCB7XG4gICAgICAgICAgICBlbnVtZXJhYmxlOiB0cnVlLFxuICAgICAgICAgICAgY29uZmlndXJhYmxlOiB0cnVlLFxuICAgICAgICAgICAgd3JpdGFibGU6IHRydWUsXG4gICAgICAgICAgICB2YWx1ZTogdm9pZCAwXG4gICAgICAgIH0pO1xuICAgICAgICB0aGlzLm5hbWUgPSBuYW1lID8/IGNhdXNlLm5hbWU7XG4gICAgICAgIHRoaXMuY29kZSA9IGNvZGU7XG4gICAgfVxufVxuIiwgImltcG9ydCB7IGlzVmFsaWREb21haW4gfSBmcm9tICcuL2lzVmFsaWREb21haW4uanMnO1xuaW1wb3J0IHsgV2ViQXV0aG5FcnJvciB9IGZyb20gJy4vd2ViQXV0aG5FcnJvci5qcyc7XG4vKipcbiAqIEF0dGVtcHQgdG8gaW50dWl0IF93aHlfIGFuIGVycm9yIHdhcyByYWlzZWQgYWZ0ZXIgY2FsbGluZyBgbmF2aWdhdG9yLmNyZWRlbnRpYWxzLmNyZWF0ZSgpYFxuICovXG5leHBvcnQgZnVuY3Rpb24gaWRlbnRpZnlSZWdpc3RyYXRpb25FcnJvcih7IGVycm9yLCBvcHRpb25zLCB9KSB7XG4gICAgY29uc3QgeyBwdWJsaWNLZXkgfSA9IG9wdGlvbnM7XG4gICAgaWYgKCFwdWJsaWNLZXkpIHtcbiAgICAgICAgdGhyb3cgRXJyb3IoJ29wdGlvbnMgd2FzIG1pc3NpbmcgcmVxdWlyZWQgcHVibGljS2V5IHByb3BlcnR5Jyk7XG4gICAgfVxuICAgIGlmIChlcnJvci5uYW1lID09PSAnQWJvcnRFcnJvcicpIHtcbiAgICAgICAgaWYgKG9wdGlvbnMuc2lnbmFsIGluc3RhbmNlb2YgQWJvcnRTaWduYWwpIHtcbiAgICAgICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLWNyZWF0ZUNyZWRlbnRpYWwgKFN0ZXAgMTYpXG4gICAgICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgICAgIG1lc3NhZ2U6ICdSZWdpc3RyYXRpb24gY2VyZW1vbnkgd2FzIHNlbnQgYW4gYWJvcnQgc2lnbmFsJyxcbiAgICAgICAgICAgICAgICBjb2RlOiAnRVJST1JfQ0VSRU1PTllfQUJPUlRFRCcsXG4gICAgICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ0NvbnN0cmFpbnRFcnJvcicpIHtcbiAgICAgICAgaWYgKHB1YmxpY0tleS5hdXRoZW50aWNhdG9yU2VsZWN0aW9uPy5yZXF1aXJlUmVzaWRlbnRLZXkgPT09IHRydWUpIHtcbiAgICAgICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLW9wLW1ha2UtY3JlZCAoU3RlcCA0KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiAnRGlzY292ZXJhYmxlIGNyZWRlbnRpYWxzIHdlcmUgcmVxdWlyZWQgYnV0IG5vIGF2YWlsYWJsZSBhdXRoZW50aWNhdG9yIHN1cHBvcnRlZCBpdCcsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0FVVEhFTlRJQ0FUT1JfTUlTU0lOR19ESVNDT1ZFUkFCTEVfQ1JFREVOVElBTF9TVVBQT1JUJyxcbiAgICAgICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChcbiAgICAgICAgLy8gQHRzLWlnbm9yZTogYG1lZGlhdGlvbmAgZG9lc24ndCB5ZXQgZXhpc3Qgb24gQ3JlZGVudGlhbENyZWF0aW9uT3B0aW9ucyBidXQgaXQncyBwb3NzaWJsZSBhcyBvZiBTZXB0IDIwMjRcbiAgICAgICAgb3B0aW9ucy5tZWRpYXRpb24gPT09ICdjb25kaXRpb25hbCcgJiZcbiAgICAgICAgICAgIHB1YmxpY0tleS5hdXRoZW50aWNhdG9yU2VsZWN0aW9uPy51c2VyVmVyaWZpY2F0aW9uID09PSAncmVxdWlyZWQnKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3czYy5naXRodWIuaW8vd2ViYXV0aG4vI3NjdG4tY3JlYXRlQ3JlZGVudGlhbCAoU3RlcCAyMi40KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiAnVXNlciB2ZXJpZmljYXRpb24gd2FzIHJlcXVpcmVkIGR1cmluZyBhdXRvbWF0aWMgcmVnaXN0cmF0aW9uIGJ1dCBpdCBjb3VsZCBub3QgYmUgcGVyZm9ybWVkJyxcbiAgICAgICAgICAgICAgICBjb2RlOiAnRVJST1JfQVVUT19SRUdJU1RFUl9VU0VSX1ZFUklGSUNBVElPTl9GQUlMVVJFJyxcbiAgICAgICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChwdWJsaWNLZXkuYXV0aGVudGljYXRvclNlbGVjdGlvbj8udXNlclZlcmlmaWNhdGlvbiA9PT0gJ3JlcXVpcmVkJykge1xuICAgICAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tb3AtbWFrZS1jcmVkIChTdGVwIDUpXG4gICAgICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgICAgIG1lc3NhZ2U6ICdVc2VyIHZlcmlmaWNhdGlvbiB3YXMgcmVxdWlyZWQgYnV0IG5vIGF2YWlsYWJsZSBhdXRoZW50aWNhdG9yIHN1cHBvcnRlZCBpdCcsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0FVVEhFTlRJQ0FUT1JfTUlTU0lOR19VU0VSX1ZFUklGSUNBVElPTl9TVVBQT1JUJyxcbiAgICAgICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBlbHNlIGlmIChlcnJvci5uYW1lID09PSAnSW52YWxpZFN0YXRlRXJyb3InKSB7XG4gICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLWNyZWF0ZUNyZWRlbnRpYWwgKFN0ZXAgMjApXG4gICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLW9wLW1ha2UtY3JlZCAoU3RlcCAzKVxuICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgbWVzc2FnZTogJ1RoZSBhdXRoZW50aWNhdG9yIHdhcyBwcmV2aW91c2x5IHJlZ2lzdGVyZWQnLFxuICAgICAgICAgICAgY29kZTogJ0VSUk9SX0FVVEhFTlRJQ0FUT1JfUFJFVklPVVNMWV9SRUdJU1RFUkVEJyxcbiAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGVsc2UgaWYgKGVycm9yLm5hbWUgPT09ICdOb3RBbGxvd2VkRXJyb3InKSB7XG4gICAgICAgIC8qKlxuICAgICAgICAgKiBQYXNzIHRoZSBlcnJvciBkaXJlY3RseSB0aHJvdWdoLiBQbGF0Zm9ybXMgYXJlIG92ZXJsb2FkaW5nIHRoaXMgZXJyb3IgYmV5b25kIHdoYXQgdGhlIHNwZWNcbiAgICAgICAgICogZGVmaW5lcyBhbmQgd2UgZG9uJ3Qgd2FudCB0byBvdmVyd3JpdGUgcG90ZW50aWFsbHkgdXNlZnVsIGVycm9yIG1lc3NhZ2VzLlxuICAgICAgICAgKi9cbiAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgIG1lc3NhZ2U6IGVycm9yLm1lc3NhZ2UsXG4gICAgICAgICAgICBjb2RlOiAnRVJST1JfUEFTU1RIUk9VR0hfU0VFX0NBVVNFX1BST1BFUlRZJyxcbiAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGVsc2UgaWYgKGVycm9yLm5hbWUgPT09ICdOb3RTdXBwb3J0ZWRFcnJvcicpIHtcbiAgICAgICAgY29uc3QgdmFsaWRQdWJLZXlDcmVkUGFyYW1zID0gcHVibGljS2V5LnB1YktleUNyZWRQYXJhbXMuZmlsdGVyKChwYXJhbSkgPT4gcGFyYW0udHlwZSA9PT0gJ3B1YmxpYy1rZXknKTtcbiAgICAgICAgaWYgKHZhbGlkUHViS2V5Q3JlZFBhcmFtcy5sZW5ndGggPT09IDApIHtcbiAgICAgICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLWNyZWF0ZUNyZWRlbnRpYWwgKFN0ZXAgMTApXG4gICAgICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgICAgIG1lc3NhZ2U6ICdObyBlbnRyeSBpbiBwdWJLZXlDcmVkUGFyYW1zIHdhcyBvZiB0eXBlIFwicHVibGljLWtleVwiJyxcbiAgICAgICAgICAgICAgICBjb2RlOiAnRVJST1JfTUFMRk9STUVEX1BVQktFWUNSRURQQVJBTVMnLFxuICAgICAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLW9wLW1ha2UtY3JlZCAoU3RlcCAyKVxuICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgbWVzc2FnZTogJ05vIGF2YWlsYWJsZSBhdXRoZW50aWNhdG9yIHN1cHBvcnRlZCBhbnkgb2YgdGhlIHNwZWNpZmllZCBwdWJLZXlDcmVkUGFyYW1zIGFsZ29yaXRobXMnLFxuICAgICAgICAgICAgY29kZTogJ0VSUk9SX0FVVEhFTlRJQ0FUT1JfTk9fU1VQUE9SVEVEX1BVQktFWUNSRURQQVJBTVNfQUxHJyxcbiAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGVsc2UgaWYgKGVycm9yLm5hbWUgPT09ICdTZWN1cml0eUVycm9yJykge1xuICAgICAgICBjb25zdCBlZmZlY3RpdmVEb21haW4gPSBnbG9iYWxUaGlzLmxvY2F0aW9uLmhvc3RuYW1lO1xuICAgICAgICBpZiAoIWlzVmFsaWREb21haW4oZWZmZWN0aXZlRG9tYWluKSkge1xuICAgICAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tY3JlYXRlQ3JlZGVudGlhbCAoU3RlcCA3KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiBgJHtnbG9iYWxUaGlzLmxvY2F0aW9uLmhvc3RuYW1lfSBpcyBhbiBpbnZhbGlkIGRvbWFpbmAsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0lOVkFMSURfRE9NQUlOJyxcbiAgICAgICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChwdWJsaWNLZXkucnAuaWQgIT09IGVmZmVjdGl2ZURvbWFpbikge1xuICAgICAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tY3JlYXRlQ3JlZGVudGlhbCAoU3RlcCA4KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiBgVGhlIFJQIElEIFwiJHtwdWJsaWNLZXkucnAuaWR9XCIgaXMgaW52YWxpZCBmb3IgdGhpcyBkb21haW5gLFxuICAgICAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9JTlZBTElEX1JQX0lEJyxcbiAgICAgICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBlbHNlIGlmIChlcnJvci5uYW1lID09PSAnVHlwZUVycm9yJykge1xuICAgICAgICBpZiAocHVibGljS2V5LnVzZXIuaWQuYnl0ZUxlbmd0aCA8IDEgfHwgcHVibGljS2V5LnVzZXIuaWQuYnl0ZUxlbmd0aCA+IDY0KSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1jcmVhdGVDcmVkZW50aWFsIChTdGVwIDUpXG4gICAgICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgICAgIG1lc3NhZ2U6ICdVc2VyIElEIHdhcyBub3QgYmV0d2VlbiAxIGFuZCA2NCBjaGFyYWN0ZXJzJyxcbiAgICAgICAgICAgICAgICBjb2RlOiAnRVJST1JfSU5WQUxJRF9VU0VSX0lEX0xFTkdUSCcsXG4gICAgICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ1Vua25vd25FcnJvcicpIHtcbiAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tb3AtbWFrZS1jcmVkIChTdGVwIDEpXG4gICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLW9wLW1ha2UtY3JlZCAoU3RlcCA4KVxuICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgbWVzc2FnZTogJ1RoZSBhdXRoZW50aWNhdG9yIHdhcyB1bmFibGUgdG8gcHJvY2VzcyB0aGUgc3BlY2lmaWVkIG9wdGlvbnMsIG9yIGNvdWxkIG5vdCBjcmVhdGUgYSBuZXcgY3JlZGVudGlhbCcsXG4gICAgICAgICAgICBjb2RlOiAnRVJST1JfQVVUSEVOVElDQVRPUl9HRU5FUkFMX0VSUk9SJyxcbiAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIHJldHVybiBlcnJvcjtcbn1cbiIsICJjbGFzcyBCYXNlV2ViQXV0aG5BYm9ydFNlcnZpY2Uge1xuICAgIGNvbnN0cnVjdG9yKCkge1xuICAgICAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkodGhpcywgXCJjb250cm9sbGVyXCIsIHtcbiAgICAgICAgICAgIGVudW1lcmFibGU6IHRydWUsXG4gICAgICAgICAgICBjb25maWd1cmFibGU6IHRydWUsXG4gICAgICAgICAgICB3cml0YWJsZTogdHJ1ZSxcbiAgICAgICAgICAgIHZhbHVlOiB2b2lkIDBcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGNyZWF0ZU5ld0Fib3J0U2lnbmFsKCkge1xuICAgICAgICAvLyBBYm9ydCBhbnkgZXhpc3RpbmcgY2FsbHMgdG8gbmF2aWdhdG9yLmNyZWRlbnRpYWxzLmNyZWF0ZSgpIG9yIG5hdmlnYXRvci5jcmVkZW50aWFscy5nZXQoKVxuICAgICAgICBpZiAodGhpcy5jb250cm9sbGVyKSB7XG4gICAgICAgICAgICBjb25zdCBhYm9ydEVycm9yID0gbmV3IEVycm9yKCdDYW5jZWxsaW5nIGV4aXN0aW5nIFdlYkF1dGhuIEFQSSBjYWxsIGZvciBuZXcgb25lJyk7XG4gICAgICAgICAgICBhYm9ydEVycm9yLm5hbWUgPSAnQWJvcnRFcnJvcic7XG4gICAgICAgICAgICB0aGlzLmNvbnRyb2xsZXIuYWJvcnQoYWJvcnRFcnJvcik7XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgbmV3Q29udHJvbGxlciA9IG5ldyBBYm9ydENvbnRyb2xsZXIoKTtcbiAgICAgICAgdGhpcy5jb250cm9sbGVyID0gbmV3Q29udHJvbGxlcjtcbiAgICAgICAgcmV0dXJuIG5ld0NvbnRyb2xsZXIuc2lnbmFsO1xuICAgIH1cbiAgICBjYW5jZWxDZXJlbW9ueSgpIHtcbiAgICAgICAgaWYgKHRoaXMuY29udHJvbGxlcikge1xuICAgICAgICAgICAgY29uc3QgYWJvcnRFcnJvciA9IG5ldyBFcnJvcignTWFudWFsbHkgY2FuY2VsbGluZyBleGlzdGluZyBXZWJBdXRobiBBUEkgY2FsbCcpO1xuICAgICAgICAgICAgYWJvcnRFcnJvci5uYW1lID0gJ0Fib3J0RXJyb3InO1xuICAgICAgICAgICAgdGhpcy5jb250cm9sbGVyLmFib3J0KGFib3J0RXJyb3IpO1xuICAgICAgICAgICAgdGhpcy5jb250cm9sbGVyID0gdW5kZWZpbmVkO1xuICAgICAgICB9XG4gICAgfVxufVxuLyoqXG4gKiBBIHNlcnZpY2Ugc2luZ2xldG9uIHRvIGhlbHAgZW5zdXJlIHRoYXQgb25seSBhIHNpbmdsZSBXZWJBdXRobiBjZXJlbW9ueSBpcyBhY3RpdmUgYXQgYSB0aW1lLlxuICpcbiAqIFVzZXJzIG9mICoqQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIqKiBzaG91bGRuJ3QgdHlwaWNhbGx5IG5lZWQgdG8gdXNlIHRoaXMsIGJ1dCBpdCBjYW4gaGVscCBlLmcuXG4gKiBkZXZlbG9wZXJzIGJ1aWxkaW5nIHByb2plY3RzIHRoYXQgdXNlIGNsaWVudC1zaWRlIHJvdXRpbmcgdG8gYmV0dGVyIGNvbnRyb2wgdGhlIGJlaGF2aW9yIG9mXG4gKiB0aGVpciBVWCBpbiByZXNwb25zZSB0byByb3V0ZXIgbmF2aWdhdGlvbiBldmVudHMuXG4gKi9cbmV4cG9ydCBjb25zdCBXZWJBdXRobkFib3J0U2VydmljZSA9IG5ldyBCYXNlV2ViQXV0aG5BYm9ydFNlcnZpY2UoKTtcbiIsICJjb25zdCBhdHRhY2htZW50cyA9IFsnY3Jvc3MtcGxhdGZvcm0nLCAncGxhdGZvcm0nXTtcbi8qKlxuICogSWYgcG9zc2libGUgY29lcmNlIGEgYHN0cmluZ2AgdmFsdWUgaW50byBhIGtub3duIGBBdXRoZW50aWNhdG9yQXR0YWNobWVudGBcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIHRvQXV0aGVudGljYXRvckF0dGFjaG1lbnQoYXR0YWNobWVudCkge1xuICAgIGlmICghYXR0YWNobWVudCkge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIGlmIChhdHRhY2htZW50cy5pbmRleE9mKGF0dGFjaG1lbnQpIDwgMCkge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIHJldHVybiBhdHRhY2htZW50O1xufVxuIiwgImltcG9ydCB7IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nIH0gZnJvbSAnLi4vaGVscGVycy9idWZmZXJUb0Jhc2U2NFVSTFN0cmluZy5qcyc7XG5pbXBvcnQgeyBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlciB9IGZyb20gJy4uL2hlbHBlcnMvYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIuanMnO1xuaW1wb3J0IHsgYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4gfSBmcm9tICcuLi9oZWxwZXJzL2Jyb3dzZXJTdXBwb3J0c1dlYkF1dGhuLmpzJztcbmltcG9ydCB7IHRvUHVibGljS2V5Q3JlZGVudGlhbERlc2NyaXB0b3IgfSBmcm9tICcuLi9oZWxwZXJzL3RvUHVibGljS2V5Q3JlZGVudGlhbERlc2NyaXB0b3IuanMnO1xuaW1wb3J0IHsgaWRlbnRpZnlSZWdpc3RyYXRpb25FcnJvciB9IGZyb20gJy4uL2hlbHBlcnMvaWRlbnRpZnlSZWdpc3RyYXRpb25FcnJvci5qcyc7XG5pbXBvcnQgeyBXZWJBdXRobkFib3J0U2VydmljZSB9IGZyb20gJy4uL2hlbHBlcnMvd2ViQXV0aG5BYm9ydFNlcnZpY2UuanMnO1xuaW1wb3J0IHsgdG9BdXRoZW50aWNhdG9yQXR0YWNobWVudCB9IGZyb20gJy4uL2hlbHBlcnMvdG9BdXRoZW50aWNhdG9yQXR0YWNobWVudC5qcyc7XG4vKipcbiAqIEJlZ2luIGF1dGhlbnRpY2F0b3IgXCJyZWdpc3RyYXRpb25cIiB2aWEgV2ViQXV0aG4gYXR0ZXN0YXRpb25cbiAqXG4gKiBAcGFyYW0gb3B0aW9uc0pTT04gT3V0cHV0IGZyb20gKipAc2ltcGxld2ViYXV0aG4vc2VydmVyKioncyBgZ2VuZXJhdGVSZWdpc3RyYXRpb25PcHRpb25zKClgXG4gKiBAcGFyYW0gdXNlQXV0b1JlZ2lzdGVyIChPcHRpb25hbCkgVHJ5IHRvIHNpbGVudGx5IGNyZWF0ZSBhIHBhc3NrZXkgd2l0aCB0aGUgcGFzc3dvcmQgbWFuYWdlciB0aGF0IHRoZSB1c2VyIGp1c3Qgc2lnbmVkIGluIHdpdGguIERlZmF1bHRzIHRvIGBmYWxzZWAuXG4gKi9cbmV4cG9ydCBhc3luYyBmdW5jdGlvbiBzdGFydFJlZ2lzdHJhdGlvbihvcHRpb25zKSB7XG4gICAgLy8gQHRzLWlnbm9yZTogSW50ZW50aW9uYWxseSBjaGVjayBmb3Igb2xkIGNhbGwgc3RydWN0dXJlIHRvIHdhcm4gYWJvdXQgaW1wcm9wZXIgQVBJIGNhbGxcbiAgICBpZiAoIW9wdGlvbnMub3B0aW9uc0pTT04gJiYgb3B0aW9ucy5jaGFsbGVuZ2UpIHtcbiAgICAgICAgY29uc29sZS53YXJuKCdzdGFydFJlZ2lzdHJhdGlvbigpIHdhcyBub3QgY2FsbGVkIGNvcnJlY3RseS4gSXQgd2lsbCB0cnkgdG8gY29udGludWUgd2l0aCB0aGUgcHJvdmlkZWQgb3B0aW9ucywgYnV0IHRoaXMgY2FsbCBzaG91bGQgYmUgcmVmYWN0b3JlZCB0byB1c2UgdGhlIGV4cGVjdGVkIGNhbGwgc3RydWN0dXJlIGluc3RlYWQuIFNlZSBodHRwczovL3NpbXBsZXdlYmF1dGhuLmRldi9kb2NzL3BhY2thZ2VzL2Jyb3dzZXIjdHlwZWVycm9yLWNhbm5vdC1yZWFkLXByb3BlcnRpZXMtb2YtdW5kZWZpbmVkLXJlYWRpbmctY2hhbGxlbmdlIGZvciBtb3JlIGluZm9ybWF0aW9uLicpO1xuICAgICAgICAvLyBAdHMtaWdub3JlOiBSZWFzc2lnbiB0aGUgb3B0aW9ucywgcGFzc2VkIGluIGFzIGEgcG9zaXRpb25hbCBhcmd1bWVudCwgdG8gdGhlIGV4cGVjdGVkIHZhcmlhYmxlXG4gICAgICAgIG9wdGlvbnMgPSB7IG9wdGlvbnNKU09OOiBvcHRpb25zIH07XG4gICAgfVxuICAgIGNvbnN0IHsgb3B0aW9uc0pTT04sIHVzZUF1dG9SZWdpc3RlciA9IGZhbHNlIH0gPSBvcHRpb25zO1xuICAgIGlmICghYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4oKSkge1xuICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1dlYkF1dGhuIGlzIG5vdCBzdXBwb3J0ZWQgaW4gdGhpcyBicm93c2VyJyk7XG4gICAgfVxuICAgIC8vIFdlIG5lZWQgdG8gY29udmVydCBzb21lIHZhbHVlcyB0byBVaW50OEFycmF5cyBiZWZvcmUgcGFzc2luZyB0aGUgY3JlZGVudGlhbHMgdG8gdGhlIG5hdmlnYXRvclxuICAgIGNvbnN0IHB1YmxpY0tleSA9IHtcbiAgICAgICAgLi4ub3B0aW9uc0pTT04sXG4gICAgICAgIGNoYWxsZW5nZTogYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIob3B0aW9uc0pTT04uY2hhbGxlbmdlKSxcbiAgICAgICAgdXNlcjoge1xuICAgICAgICAgICAgLi4ub3B0aW9uc0pTT04udXNlcixcbiAgICAgICAgICAgIGlkOiBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlcihvcHRpb25zSlNPTi51c2VyLmlkKSxcbiAgICAgICAgfSxcbiAgICAgICAgZXhjbHVkZUNyZWRlbnRpYWxzOiBvcHRpb25zSlNPTi5leGNsdWRlQ3JlZGVudGlhbHM/Lm1hcCh0b1B1YmxpY0tleUNyZWRlbnRpYWxEZXNjcmlwdG9yKSxcbiAgICB9O1xuICAgIC8vIFByZXBhcmUgb3B0aW9ucyBmb3IgYC5jcmVhdGUoKWBcbiAgICBjb25zdCBjcmVhdGVPcHRpb25zID0ge307XG4gICAgLyoqXG4gICAgICogVHJ5IHRvIHVzZSBjb25kaXRpb25hbCBjcmVhdGUgdG8gcmVnaXN0ZXIgYSBwYXNza2V5IGZvciB0aGUgdXNlciB3aXRoIHRoZSBwYXNzd29yZCBtYW5hZ2VyXG4gICAgICogdGhlIHVzZXIganVzdCB1c2VkIHRvIGF1dGhlbnRpY2F0ZSB3aXRoLiBUaGUgdXNlciB3b24ndCBiZSBzaG93biBhbnkgcHJvbWluZW50IFVJIGJ5IHRoZVxuICAgICAqIGJyb3dzZXIuXG4gICAgICovXG4gICAgaWYgKHVzZUF1dG9SZWdpc3Rlcikge1xuICAgICAgICAvLyBAdHMtaWdub3JlOiBgbWVkaWF0aW9uYCBkb2Vzbid0IHlldCBleGlzdCBvbiBDcmVkZW50aWFsQ3JlYXRpb25PcHRpb25zIGJ1dCBpdCdzIHBvc3NpYmxlIGFzIG9mIFNlcHQgMjAyNFxuICAgICAgICBjcmVhdGVPcHRpb25zLm1lZGlhdGlvbiA9ICdjb25kaXRpb25hbCc7XG4gICAgfVxuICAgIC8vIEZpbmFsaXplIG9wdGlvbnNcbiAgICBjcmVhdGVPcHRpb25zLnB1YmxpY0tleSA9IHB1YmxpY0tleTtcbiAgICAvLyBTZXQgdXAgdGhlIGFiaWxpdHkgdG8gY2FuY2VsIHRoaXMgcmVxdWVzdCBpZiB0aGUgdXNlciBhdHRlbXB0cyBhbm90aGVyXG4gICAgY3JlYXRlT3B0aW9ucy5zaWduYWwgPSBXZWJBdXRobkFib3J0U2VydmljZS5jcmVhdGVOZXdBYm9ydFNpZ25hbCgpO1xuICAgIC8vIFdhaXQgZm9yIHRoZSB1c2VyIHRvIGNvbXBsZXRlIGF0dGVzdGF0aW9uXG4gICAgbGV0IGNyZWRlbnRpYWw7XG4gICAgdHJ5IHtcbiAgICAgICAgY3JlZGVudGlhbCA9IChhd2FpdCBuYXZpZ2F0b3IuY3JlZGVudGlhbHMuY3JlYXRlKGNyZWF0ZU9wdGlvbnMpKTtcbiAgICB9XG4gICAgY2F0Y2ggKGVycikge1xuICAgICAgICB0aHJvdyBpZGVudGlmeVJlZ2lzdHJhdGlvbkVycm9yKHsgZXJyb3I6IGVyciwgb3B0aW9uczogY3JlYXRlT3B0aW9ucyB9KTtcbiAgICB9XG4gICAgaWYgKCFjcmVkZW50aWFsKSB7XG4gICAgICAgIHRocm93IG5ldyBFcnJvcignUmVnaXN0cmF0aW9uIHdhcyBub3QgY29tcGxldGVkJyk7XG4gICAgfVxuICAgIGNvbnN0IHsgaWQsIHJhd0lkLCByZXNwb25zZSwgdHlwZSB9ID0gY3JlZGVudGlhbDtcbiAgICAvLyBDb250aW51ZSB0byBwbGF5IGl0IHNhZmUgd2l0aCBgZ2V0VHJhbnNwb3J0cygpYCBmb3Igbm93LCBldmVuIHdoZW4gTDMgdHlwZXMgc2F5IGl0J3MgcmVxdWlyZWRcbiAgICBsZXQgdHJhbnNwb3J0cyA9IHVuZGVmaW5lZDtcbiAgICBpZiAodHlwZW9mIHJlc3BvbnNlLmdldFRyYW5zcG9ydHMgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgdHJhbnNwb3J0cyA9IHJlc3BvbnNlLmdldFRyYW5zcG9ydHMoKTtcbiAgICB9XG4gICAgLy8gTDMgc2F5cyB0aGlzIGlzIHJlcXVpcmVkLCBidXQgYnJvd3NlciBhbmQgd2VidmlldyBzdXBwb3J0IGFyZSBzdGlsbCBub3QgZ3VhcmFudGVlZC5cbiAgICBsZXQgcmVzcG9uc2VQdWJsaWNLZXlBbGdvcml0aG0gPSB1bmRlZmluZWQ7XG4gICAgaWYgKHR5cGVvZiByZXNwb25zZS5nZXRQdWJsaWNLZXlBbGdvcml0aG0gPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIHJlc3BvbnNlUHVibGljS2V5QWxnb3JpdGhtID0gcmVzcG9uc2UuZ2V0UHVibGljS2V5QWxnb3JpdGhtKCk7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICB3YXJuT25Ccm9rZW5JbXBsZW1lbnRhdGlvbignZ2V0UHVibGljS2V5QWxnb3JpdGhtKCknLCBlcnJvcik7XG4gICAgICAgIH1cbiAgICB9XG4gICAgbGV0IHJlc3BvbnNlUHVibGljS2V5ID0gdW5kZWZpbmVkO1xuICAgIGlmICh0eXBlb2YgcmVzcG9uc2UuZ2V0UHVibGljS2V5ID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICBjb25zdCBfcHVibGljS2V5ID0gcmVzcG9uc2UuZ2V0UHVibGljS2V5KCk7XG4gICAgICAgICAgICBpZiAoX3B1YmxpY0tleSAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIHJlc3BvbnNlUHVibGljS2V5ID0gYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcoX3B1YmxpY0tleSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICB3YXJuT25Ccm9rZW5JbXBsZW1lbnRhdGlvbignZ2V0UHVibGljS2V5KCknLCBlcnJvcik7XG4gICAgICAgIH1cbiAgICB9XG4gICAgLy8gTDMgc2F5cyB0aGlzIGlzIHJlcXVpcmVkLCBidXQgYnJvd3NlciBhbmQgd2VidmlldyBzdXBwb3J0IGFyZSBzdGlsbCBub3QgZ3VhcmFudGVlZC5cbiAgICBsZXQgcmVzcG9uc2VBdXRoZW50aWNhdG9yRGF0YTtcbiAgICBpZiAodHlwZW9mIHJlc3BvbnNlLmdldEF1dGhlbnRpY2F0b3JEYXRhID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICByZXNwb25zZUF1dGhlbnRpY2F0b3JEYXRhID0gYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcocmVzcG9uc2UuZ2V0QXV0aGVudGljYXRvckRhdGEoKSk7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICB3YXJuT25Ccm9rZW5JbXBsZW1lbnRhdGlvbignZ2V0QXV0aGVudGljYXRvckRhdGEoKScsIGVycm9yKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZXR1cm4ge1xuICAgICAgICBpZCxcbiAgICAgICAgcmF3SWQ6IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKHJhd0lkKSxcbiAgICAgICAgcmVzcG9uc2U6IHtcbiAgICAgICAgICAgIGF0dGVzdGF0aW9uT2JqZWN0OiBidWZmZXJUb0Jhc2U2NFVSTFN0cmluZyhyZXNwb25zZS5hdHRlc3RhdGlvbk9iamVjdCksXG4gICAgICAgICAgICBjbGllbnREYXRhSlNPTjogYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcocmVzcG9uc2UuY2xpZW50RGF0YUpTT04pLFxuICAgICAgICAgICAgdHJhbnNwb3J0cyxcbiAgICAgICAgICAgIHB1YmxpY0tleUFsZ29yaXRobTogcmVzcG9uc2VQdWJsaWNLZXlBbGdvcml0aG0sXG4gICAgICAgICAgICBwdWJsaWNLZXk6IHJlc3BvbnNlUHVibGljS2V5LFxuICAgICAgICAgICAgYXV0aGVudGljYXRvckRhdGE6IHJlc3BvbnNlQXV0aGVudGljYXRvckRhdGEsXG4gICAgICAgIH0sXG4gICAgICAgIHR5cGUsXG4gICAgICAgIGNsaWVudEV4dGVuc2lvblJlc3VsdHM6IGNyZWRlbnRpYWwuZ2V0Q2xpZW50RXh0ZW5zaW9uUmVzdWx0cygpLFxuICAgICAgICBhdXRoZW50aWNhdG9yQXR0YWNobWVudDogdG9BdXRoZW50aWNhdG9yQXR0YWNobWVudChjcmVkZW50aWFsLmF1dGhlbnRpY2F0b3JBdHRhY2htZW50KSxcbiAgICB9O1xufVxuLyoqXG4gKiBWaXNpYmx5IHdhcm4gd2hlbiB3ZSBkZXRlY3QgYW4gaXNzdWUgcmVsYXRlZCB0byBhIHBhc3NrZXkgcHJvdmlkZXIgaW50ZXJjZXB0aW5nIFdlYkF1dGhuIEFQSVxuICogY2FsbHNcbiAqL1xuZnVuY3Rpb24gd2Fybk9uQnJva2VuSW1wbGVtZW50YXRpb24obWV0aG9kTmFtZSwgY2F1c2UpIHtcbiAgICBjb25zb2xlLndhcm4oYFRoZSBicm93c2VyIGV4dGVuc2lvbiB0aGF0IGludGVyY2VwdGVkIHRoaXMgV2ViQXV0aG4gQVBJIGNhbGwgaW5jb3JyZWN0bHkgaW1wbGVtZW50ZWQgJHttZXRob2ROYW1lfS4gWW91IHNob3VsZCByZXBvcnQgdGhpcyBlcnJvciB0byB0aGVtLlxcbmAsIGNhdXNlKTtcbn1cbiIsICJpbXBvcnQgeyBicm93c2VyU3VwcG9ydHNXZWJBdXRobiB9IGZyb20gJy4vYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4uanMnO1xuLyoqXG4gKiBEZXRlcm1pbmUgaWYgdGhlIGJyb3dzZXIgc3VwcG9ydHMgY29uZGl0aW9uYWwgVUksIHNvIHRoYXQgV2ViQXV0aG4gY3JlZGVudGlhbHMgY2FuXG4gKiBiZSBzaG93biB0byB0aGUgdXNlciBpbiB0aGUgYnJvd3NlcidzIHR5cGljYWwgcGFzc3dvcmQgYXV0b2ZpbGwgcG9wdXAuXG4gKi9cbmV4cG9ydCBmdW5jdGlvbiBicm93c2VyU3VwcG9ydHNXZWJBdXRobkF1dG9maWxsKCkge1xuICAgIGlmICghYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4oKSkge1xuICAgICAgICByZXR1cm4gX2Jyb3dzZXJTdXBwb3J0c1dlYkF1dGhuQXV0b2ZpbGxJbnRlcm5hbHMuc3R1YlRoaXMobmV3IFByb21pc2UoKHJlc29sdmUpID0+IHJlc29sdmUoZmFsc2UpKSk7XG4gICAgfVxuICAgIC8qKlxuICAgICAqIEkgZG9uJ3QgbGlrZSB0aGUgYGFzIHVua25vd25gIGhlcmUgYnV0IHRoZXJlJ3MgYSBgZGVjbGFyZSB2YXIgUHVibGljS2V5Q3JlZGVudGlhbGAgaW5cbiAgICAgKiBUUycgRE9NIGxpYiB0aGF0J3MgbWFraW5nIGl0IGRpZmZpY3VsdCBmb3IgbWUgdG8ganVzdCBnbyBgYXMgUHVibGljS2V5Q3JlZGVudGlhbEZ1dHVyZWAgYXMgSVxuICAgICAqIHdhbnQuIEkgdGhpbmsgSSdtIGZpbmUgd2l0aCB0aGlzIGZvciBub3cgc2luY2UgaXQncyBfc3VwcG9zZWRfIHRvIGJlIHRlbXBvcmFyeSwgdW50aWwgVFMgdHlwZXNcbiAgICAgKiBoYXZlIGEgY2hhbmNlIHRvIGNhdGNoIHVwLlxuICAgICAqL1xuICAgIGNvbnN0IGdsb2JhbFB1YmxpY0tleUNyZWRlbnRpYWwgPSBnbG9iYWxUaGlzXG4gICAgICAgIC5QdWJsaWNLZXlDcmVkZW50aWFsO1xuICAgIGlmIChnbG9iYWxQdWJsaWNLZXlDcmVkZW50aWFsPy5pc0NvbmRpdGlvbmFsTWVkaWF0aW9uQXZhaWxhYmxlID09PSB1bmRlZmluZWQpIHtcbiAgICAgICAgcmV0dXJuIF9icm93c2VyU3VwcG9ydHNXZWJBdXRobkF1dG9maWxsSW50ZXJuYWxzLnN0dWJUaGlzKG5ldyBQcm9taXNlKChyZXNvbHZlKSA9PiByZXNvbHZlKGZhbHNlKSkpO1xuICAgIH1cbiAgICByZXR1cm4gX2Jyb3dzZXJTdXBwb3J0c1dlYkF1dGhuQXV0b2ZpbGxJbnRlcm5hbHMuc3R1YlRoaXMoZ2xvYmFsUHVibGljS2V5Q3JlZGVudGlhbC5pc0NvbmRpdGlvbmFsTWVkaWF0aW9uQXZhaWxhYmxlKCkpO1xufVxuLy8gTWFrZSBpdCBwb3NzaWJsZSB0byBzdHViIHRoZSByZXR1cm4gdmFsdWUgZHVyaW5nIHRlc3RpbmdcbmV4cG9ydCBjb25zdCBfYnJvd3NlclN1cHBvcnRzV2ViQXV0aG5BdXRvZmlsbEludGVybmFscyA9IHtcbiAgICBzdHViVGhpczogKHZhbHVlKSA9PiB2YWx1ZSxcbn07XG4iLCAiaW1wb3J0IHsgaXNWYWxpZERvbWFpbiB9IGZyb20gJy4vaXNWYWxpZERvbWFpbi5qcyc7XG5pbXBvcnQgeyBXZWJBdXRobkVycm9yIH0gZnJvbSAnLi93ZWJBdXRobkVycm9yLmpzJztcbi8qKlxuICogQXR0ZW1wdCB0byBpbnR1aXQgX3doeV8gYW4gZXJyb3Igd2FzIHJhaXNlZCBhZnRlciBjYWxsaW5nIGBuYXZpZ2F0b3IuY3JlZGVudGlhbHMuZ2V0KClgXG4gKi9cbmV4cG9ydCBmdW5jdGlvbiBpZGVudGlmeUF1dGhlbnRpY2F0aW9uRXJyb3IoeyBlcnJvciwgb3B0aW9ucywgfSkge1xuICAgIGNvbnN0IHsgcHVibGljS2V5IH0gPSBvcHRpb25zO1xuICAgIGlmICghcHVibGljS2V5KSB7XG4gICAgICAgIHRocm93IEVycm9yKCdvcHRpb25zIHdhcyBtaXNzaW5nIHJlcXVpcmVkIHB1YmxpY0tleSBwcm9wZXJ0eScpO1xuICAgIH1cbiAgICBpZiAoZXJyb3IubmFtZSA9PT0gJ0Fib3J0RXJyb3InKSB7XG4gICAgICAgIGlmIChvcHRpb25zLnNpZ25hbCBpbnN0YW5jZW9mIEFib3J0U2lnbmFsKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1jcmVhdGVDcmVkZW50aWFsIChTdGVwIDE2KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiAnQXV0aGVudGljYXRpb24gY2VyZW1vbnkgd2FzIHNlbnQgYW4gYWJvcnQgc2lnbmFsJyxcbiAgICAgICAgICAgICAgICBjb2RlOiAnRVJST1JfQ0VSRU1PTllfQUJPUlRFRCcsXG4gICAgICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ05vdEFsbG93ZWRFcnJvcicpIHtcbiAgICAgICAgLyoqXG4gICAgICAgICAqIFBhc3MgdGhlIGVycm9yIGRpcmVjdGx5IHRocm91Z2guIFBsYXRmb3JtcyBhcmUgb3ZlcmxvYWRpbmcgdGhpcyBlcnJvciBiZXlvbmQgd2hhdCB0aGUgc3BlY1xuICAgICAgICAgKiBkZWZpbmVzIGFuZCB3ZSBkb24ndCB3YW50IHRvIG92ZXJ3cml0ZSBwb3RlbnRpYWxseSB1c2VmdWwgZXJyb3IgbWVzc2FnZXMuXG4gICAgICAgICAqL1xuICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgbWVzc2FnZTogZXJyb3IubWVzc2FnZSxcbiAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9QQVNTVEhST1VHSF9TRUVfQ0FVU0VfUFJPUEVSVFknLFxuICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICB9KTtcbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ1NlY3VyaXR5RXJyb3InKSB7XG4gICAgICAgIGNvbnN0IGVmZmVjdGl2ZURvbWFpbiA9IGdsb2JhbFRoaXMubG9jYXRpb24uaG9zdG5hbWU7XG4gICAgICAgIGlmICghaXNWYWxpZERvbWFpbihlZmZlY3RpdmVEb21haW4pKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1kaXNjb3Zlci1mcm9tLWV4dGVybmFsLXNvdXJjZSAoU3RlcCA1KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiBgJHtnbG9iYWxUaGlzLmxvY2F0aW9uLmhvc3RuYW1lfSBpcyBhbiBpbnZhbGlkIGRvbWFpbmAsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0lOVkFMSURfRE9NQUlOJyxcbiAgICAgICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChwdWJsaWNLZXkucnBJZCAhPT0gZWZmZWN0aXZlRG9tYWluKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1kaXNjb3Zlci1mcm9tLWV4dGVybmFsLXNvdXJjZSAoU3RlcCA2KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiBgVGhlIFJQIElEIFwiJHtwdWJsaWNLZXkucnBJZH1cIiBpcyBpbnZhbGlkIGZvciB0aGlzIGRvbWFpbmAsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0lOVkFMSURfUlBfSUQnLFxuICAgICAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsc2UgaWYgKGVycm9yLm5hbWUgPT09ICdVbmtub3duRXJyb3InKSB7XG4gICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLW9wLWdldC1hc3NlcnRpb24gKFN0ZXAgMSlcbiAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tb3AtZ2V0LWFzc2VydGlvbiAoU3RlcCAxMilcbiAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgIG1lc3NhZ2U6ICdUaGUgYXV0aGVudGljYXRvciB3YXMgdW5hYmxlIHRvIHByb2Nlc3MgdGhlIHNwZWNpZmllZCBvcHRpb25zLCBvciBjb3VsZCBub3QgY3JlYXRlIGEgbmV3IGFzc2VydGlvbiBzaWduYXR1cmUnLFxuICAgICAgICAgICAgY29kZTogJ0VSUk9SX0FVVEhFTlRJQ0FUT1JfR0VORVJBTF9FUlJPUicsXG4gICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICByZXR1cm4gZXJyb3I7XG59XG4iLCAiaW1wb3J0IHsgYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcgfSBmcm9tICcuLi9oZWxwZXJzL2J1ZmZlclRvQmFzZTY0VVJMU3RyaW5nLmpzJztcbmltcG9ydCB7IGJhc2U2NFVSTFN0cmluZ1RvQnVmZmVyIH0gZnJvbSAnLi4vaGVscGVycy9iYXNlNjRVUkxTdHJpbmdUb0J1ZmZlci5qcyc7XG5pbXBvcnQgeyBicm93c2VyU3VwcG9ydHNXZWJBdXRobiB9IGZyb20gJy4uL2hlbHBlcnMvYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4uanMnO1xuaW1wb3J0IHsgYnJvd3NlclN1cHBvcnRzV2ViQXV0aG5BdXRvZmlsbCB9IGZyb20gJy4uL2hlbHBlcnMvYnJvd3NlclN1cHBvcnRzV2ViQXV0aG5BdXRvZmlsbC5qcyc7XG5pbXBvcnQgeyB0b1B1YmxpY0tleUNyZWRlbnRpYWxEZXNjcmlwdG9yIH0gZnJvbSAnLi4vaGVscGVycy90b1B1YmxpY0tleUNyZWRlbnRpYWxEZXNjcmlwdG9yLmpzJztcbmltcG9ydCB7IGlkZW50aWZ5QXV0aGVudGljYXRpb25FcnJvciB9IGZyb20gJy4uL2hlbHBlcnMvaWRlbnRpZnlBdXRoZW50aWNhdGlvbkVycm9yLmpzJztcbmltcG9ydCB7IFdlYkF1dGhuQWJvcnRTZXJ2aWNlIH0gZnJvbSAnLi4vaGVscGVycy93ZWJBdXRobkFib3J0U2VydmljZS5qcyc7XG5pbXBvcnQgeyB0b0F1dGhlbnRpY2F0b3JBdHRhY2htZW50IH0gZnJvbSAnLi4vaGVscGVycy90b0F1dGhlbnRpY2F0b3JBdHRhY2htZW50LmpzJztcbi8qKlxuICogQmVnaW4gYXV0aGVudGljYXRvciBcImxvZ2luXCIgdmlhIFdlYkF1dGhuIGFzc2VydGlvblxuICpcbiAqIEBwYXJhbSBvcHRpb25zSlNPTiBPdXRwdXQgZnJvbSAqKkBzaW1wbGV3ZWJhdXRobi9zZXJ2ZXIqKidzIGBnZW5lcmF0ZUF1dGhlbnRpY2F0aW9uT3B0aW9ucygpYFxuICogQHBhcmFtIHVzZUJyb3dzZXJBdXRvZmlsbCAoT3B0aW9uYWwpIEluaXRpYWxpemUgY29uZGl0aW9uYWwgVUkgdG8gZW5hYmxlIGxvZ2dpbmcgaW4gdmlhIGJyb3dzZXIgYXV0b2ZpbGwgcHJvbXB0cy4gRGVmYXVsdHMgdG8gYGZhbHNlYC5cbiAqIEBwYXJhbSB2ZXJpZnlCcm93c2VyQXV0b2ZpbGxJbnB1dCAoT3B0aW9uYWwpIEVuc3VyZSBhIHN1aXRhYmxlIGA8aW5wdXQ+YCBlbGVtZW50IGlzIHByZXNlbnQgd2hlbiBgdXNlQnJvd3NlckF1dG9maWxsYCBpcyBgdHJ1ZWAuIERlZmF1bHRzIHRvIGB0cnVlYC5cbiAqL1xuZXhwb3J0IGFzeW5jIGZ1bmN0aW9uIHN0YXJ0QXV0aGVudGljYXRpb24ob3B0aW9ucykge1xuICAgIC8vIEB0cy1pZ25vcmU6IEludGVudGlvbmFsbHkgY2hlY2sgZm9yIG9sZCBjYWxsIHN0cnVjdHVyZSB0byB3YXJuIGFib3V0IGltcHJvcGVyIEFQSSBjYWxsXG4gICAgaWYgKCFvcHRpb25zLm9wdGlvbnNKU09OICYmIG9wdGlvbnMuY2hhbGxlbmdlKSB7XG4gICAgICAgIGNvbnNvbGUud2Fybignc3RhcnRBdXRoZW50aWNhdGlvbigpIHdhcyBub3QgY2FsbGVkIGNvcnJlY3RseS4gSXQgd2lsbCB0cnkgdG8gY29udGludWUgd2l0aCB0aGUgcHJvdmlkZWQgb3B0aW9ucywgYnV0IHRoaXMgY2FsbCBzaG91bGQgYmUgcmVmYWN0b3JlZCB0byB1c2UgdGhlIGV4cGVjdGVkIGNhbGwgc3RydWN0dXJlIGluc3RlYWQuIFNlZSBodHRwczovL3NpbXBsZXdlYmF1dGhuLmRldi9kb2NzL3BhY2thZ2VzL2Jyb3dzZXIjdHlwZWVycm9yLWNhbm5vdC1yZWFkLXByb3BlcnRpZXMtb2YtdW5kZWZpbmVkLXJlYWRpbmctY2hhbGxlbmdlIGZvciBtb3JlIGluZm9ybWF0aW9uLicpO1xuICAgICAgICAvLyBAdHMtaWdub3JlOiBSZWFzc2lnbiB0aGUgb3B0aW9ucywgcGFzc2VkIGluIGFzIGEgcG9zaXRpb25hbCBhcmd1bWVudCwgdG8gdGhlIGV4cGVjdGVkIHZhcmlhYmxlXG4gICAgICAgIG9wdGlvbnMgPSB7IG9wdGlvbnNKU09OOiBvcHRpb25zIH07XG4gICAgfVxuICAgIGNvbnN0IHsgb3B0aW9uc0pTT04sIHVzZUJyb3dzZXJBdXRvZmlsbCA9IGZhbHNlLCB2ZXJpZnlCcm93c2VyQXV0b2ZpbGxJbnB1dCA9IHRydWUsIH0gPSBvcHRpb25zO1xuICAgIGlmICghYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4oKSkge1xuICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1dlYkF1dGhuIGlzIG5vdCBzdXBwb3J0ZWQgaW4gdGhpcyBicm93c2VyJyk7XG4gICAgfVxuICAgIC8vIFdlIG5lZWQgdG8gYXZvaWQgcGFzc2luZyBlbXB0eSBhcnJheSB0byBhdm9pZCBibG9ja2luZyByZXRyaWV2YWxcbiAgICAvLyBvZiBwdWJsaWMga2V5XG4gICAgbGV0IGFsbG93Q3JlZGVudGlhbHM7XG4gICAgaWYgKG9wdGlvbnNKU09OLmFsbG93Q3JlZGVudGlhbHM/Lmxlbmd0aCAhPT0gMCkge1xuICAgICAgICBhbGxvd0NyZWRlbnRpYWxzID0gb3B0aW9uc0pTT04uYWxsb3dDcmVkZW50aWFscz8ubWFwKHRvUHVibGljS2V5Q3JlZGVudGlhbERlc2NyaXB0b3IpO1xuICAgIH1cbiAgICAvLyBXZSBuZWVkIHRvIGNvbnZlcnQgc29tZSB2YWx1ZXMgdG8gVWludDhBcnJheXMgYmVmb3JlIHBhc3NpbmcgdGhlIGNyZWRlbnRpYWxzIHRvIHRoZSBuYXZpZ2F0b3JcbiAgICBjb25zdCBwdWJsaWNLZXkgPSB7XG4gICAgICAgIC4uLm9wdGlvbnNKU09OLFxuICAgICAgICBjaGFsbGVuZ2U6IGJhc2U2NFVSTFN0cmluZ1RvQnVmZmVyKG9wdGlvbnNKU09OLmNoYWxsZW5nZSksXG4gICAgICAgIGFsbG93Q3JlZGVudGlhbHMsXG4gICAgfTtcbiAgICAvLyBQcmVwYXJlIG9wdGlvbnMgZm9yIGAuZ2V0KClgXG4gICAgY29uc3QgZ2V0T3B0aW9ucyA9IHt9O1xuICAgIC8qKlxuICAgICAqIFNldCB1cCB0aGUgcGFnZSB0byBwcm9tcHQgdGhlIHVzZXIgdG8gc2VsZWN0IGEgY3JlZGVudGlhbCBmb3IgYXV0aGVudGljYXRpb24gdmlhIHRoZSBicm93c2VyJ3NcbiAgICAgKiBpbnB1dCBhdXRvZmlsbCBtZWNoYW5pc20uXG4gICAgICovXG4gICAgaWYgKHVzZUJyb3dzZXJBdXRvZmlsbCkge1xuICAgICAgICBpZiAoIShhd2FpdCBicm93c2VyU3VwcG9ydHNXZWJBdXRobkF1dG9maWxsKCkpKSB7XG4gICAgICAgICAgICB0aHJvdyBFcnJvcignQnJvd3NlciBkb2VzIG5vdCBzdXBwb3J0IFdlYkF1dGhuIGF1dG9maWxsJyk7XG4gICAgICAgIH1cbiAgICAgICAgLy8gQ2hlY2sgZm9yIGFuIDxpbnB1dD4gd2l0aCBcIndlYmF1dGhuXCIgaW4gaXRzIGBhdXRvY29tcGxldGVgIGF0dHJpYnV0ZVxuICAgICAgICBjb25zdCBlbGlnaWJsZUlucHV0cyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoXCJpbnB1dFthdXRvY29tcGxldGUkPSd3ZWJhdXRobiddXCIpO1xuICAgICAgICAvLyBXZWJBdXRobiBhdXRvZmlsbCByZXF1aXJlcyBhdCBsZWFzdCBvbmUgdmFsaWQgaW5wdXRcbiAgICAgICAgaWYgKGVsaWdpYmxlSW5wdXRzLmxlbmd0aCA8IDEgJiYgdmVyaWZ5QnJvd3NlckF1dG9maWxsSW5wdXQpIHtcbiAgICAgICAgICAgIHRocm93IEVycm9yKCdObyA8aW5wdXQ+IHdpdGggXCJ3ZWJhdXRoblwiIGFzIHRoZSBvbmx5IG9yIGxhc3QgdmFsdWUgaW4gaXRzIGBhdXRvY29tcGxldGVgIGF0dHJpYnV0ZSB3YXMgZGV0ZWN0ZWQnKTtcbiAgICAgICAgfVxuICAgICAgICAvLyBgQ3JlZGVudGlhbE1lZGlhdGlvblJlcXVpcmVtZW50YCBkb2Vzbid0IGtub3cgYWJvdXQgXCJjb25kaXRpb25hbFwiIHlldCBhcyBvZlxuICAgICAgICAvLyB0eXBlc2NyaXB0QDQuNi4zXG4gICAgICAgIGdldE9wdGlvbnMubWVkaWF0aW9uID0gJ2NvbmRpdGlvbmFsJztcbiAgICAgICAgLy8gQ29uZGl0aW9uYWwgVUkgcmVxdWlyZXMgYW4gZW1wdHkgYWxsb3cgbGlzdFxuICAgICAgICBwdWJsaWNLZXkuYWxsb3dDcmVkZW50aWFscyA9IFtdO1xuICAgIH1cbiAgICAvLyBGaW5hbGl6ZSBvcHRpb25zXG4gICAgZ2V0T3B0aW9ucy5wdWJsaWNLZXkgPSBwdWJsaWNLZXk7XG4gICAgLy8gU2V0IHVwIHRoZSBhYmlsaXR5IHRvIGNhbmNlbCB0aGlzIHJlcXVlc3QgaWYgdGhlIHVzZXIgYXR0ZW1wdHMgYW5vdGhlclxuICAgIGdldE9wdGlvbnMuc2lnbmFsID0gV2ViQXV0aG5BYm9ydFNlcnZpY2UuY3JlYXRlTmV3QWJvcnRTaWduYWwoKTtcbiAgICAvLyBXYWl0IGZvciB0aGUgdXNlciB0byBjb21wbGV0ZSBhc3NlcnRpb25cbiAgICBsZXQgY3JlZGVudGlhbDtcbiAgICB0cnkge1xuICAgICAgICBjcmVkZW50aWFsID0gKGF3YWl0IG5hdmlnYXRvci5jcmVkZW50aWFscy5nZXQoZ2V0T3B0aW9ucykpO1xuICAgIH1cbiAgICBjYXRjaCAoZXJyKSB7XG4gICAgICAgIHRocm93IGlkZW50aWZ5QXV0aGVudGljYXRpb25FcnJvcih7IGVycm9yOiBlcnIsIG9wdGlvbnM6IGdldE9wdGlvbnMgfSk7XG4gICAgfVxuICAgIGlmICghY3JlZGVudGlhbCkge1xuICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ0F1dGhlbnRpY2F0aW9uIHdhcyBub3QgY29tcGxldGVkJyk7XG4gICAgfVxuICAgIGNvbnN0IHsgaWQsIHJhd0lkLCByZXNwb25zZSwgdHlwZSB9ID0gY3JlZGVudGlhbDtcbiAgICBsZXQgdXNlckhhbmRsZSA9IHVuZGVmaW5lZDtcbiAgICBpZiAocmVzcG9uc2UudXNlckhhbmRsZSkge1xuICAgICAgICB1c2VySGFuZGxlID0gYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcocmVzcG9uc2UudXNlckhhbmRsZSk7XG4gICAgfVxuICAgIC8vIENvbnZlcnQgdmFsdWVzIHRvIGJhc2U2NCB0byBtYWtlIGl0IGVhc2llciB0byBzZW5kIGJhY2sgdG8gdGhlIHNlcnZlclxuICAgIHJldHVybiB7XG4gICAgICAgIGlkLFxuICAgICAgICByYXdJZDogYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcocmF3SWQpLFxuICAgICAgICByZXNwb25zZToge1xuICAgICAgICAgICAgYXV0aGVudGljYXRvckRhdGE6IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKHJlc3BvbnNlLmF1dGhlbnRpY2F0b3JEYXRhKSxcbiAgICAgICAgICAgIGNsaWVudERhdGFKU09OOiBidWZmZXJUb0Jhc2U2NFVSTFN0cmluZyhyZXNwb25zZS5jbGllbnREYXRhSlNPTiksXG4gICAgICAgICAgICBzaWduYXR1cmU6IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKHJlc3BvbnNlLnNpZ25hdHVyZSksXG4gICAgICAgICAgICB1c2VySGFuZGxlLFxuICAgICAgICB9LFxuICAgICAgICB0eXBlLFxuICAgICAgICBjbGllbnRFeHRlbnNpb25SZXN1bHRzOiBjcmVkZW50aWFsLmdldENsaWVudEV4dGVuc2lvblJlc3VsdHMoKSxcbiAgICAgICAgYXV0aGVudGljYXRvckF0dGFjaG1lbnQ6IHRvQXV0aGVudGljYXRvckF0dGFjaG1lbnQoY3JlZGVudGlhbC5hdXRoZW50aWNhdG9yQXR0YWNobWVudCksXG4gICAgfTtcbn1cbiIsICJpbXBvcnQge1xuICAgIGJyb3dzZXJTdXBwb3J0c1dlYkF1dGhuLFxuICAgIHN0YXJ0QXV0aGVudGljYXRpb24sXG4gICAgc3RhcnRSZWdpc3RyYXRpb24sXG59IGZyb20gJ0BzaW1wbGV3ZWJhdXRobi9icm93c2VyJztcblxud2luZG93LmJyb3dzZXJTdXBwb3J0c1dlYkF1dGhuID0gYnJvd3NlclN1cHBvcnRzV2ViQXV0aG47XG53aW5kb3cuc3RhcnRBdXRoZW50aWNhdGlvbiA9IHN0YXJ0QXV0aGVudGljYXRpb247XG53aW5kb3cuc3RhcnRSZWdpc3RyYXRpb24gPSBzdGFydFJlZ2lzdHJhdGlvbjtcbiJdLAogICJtYXBwaW5ncyI6ICI7QUFNTyxTQUFTLHdCQUF3QixRQUFRO0FBQzVDLFFBQU0sUUFBUSxJQUFJLFdBQVcsTUFBTTtBQUNuQyxNQUFJLE1BQU07QUFDVixhQUFXLFlBQVksT0FBTztBQUMxQixXQUFPLE9BQU8sYUFBYSxRQUFRO0FBQUEsRUFDdkM7QUFDQSxRQUFNLGVBQWUsS0FBSyxHQUFHO0FBQzdCLFNBQU8sYUFBYSxRQUFRLE9BQU8sR0FBRyxFQUFFLFFBQVEsT0FBTyxHQUFHLEVBQUUsUUFBUSxNQUFNLEVBQUU7QUFDaEY7OztBQ1BPLFNBQVMsd0JBQXdCLGlCQUFpQjtBQUVyRCxRQUFNLFNBQVMsZ0JBQWdCLFFBQVEsTUFBTSxHQUFHLEVBQUUsUUFBUSxNQUFNLEdBQUc7QUFRbkUsUUFBTSxhQUFhLElBQUssT0FBTyxTQUFTLEtBQU07QUFDOUMsUUFBTSxTQUFTLE9BQU8sT0FBTyxPQUFPLFNBQVMsV0FBVyxHQUFHO0FBRTNELFFBQU0sU0FBUyxLQUFLLE1BQU07QUFFMUIsUUFBTSxTQUFTLElBQUksWUFBWSxPQUFPLE1BQU07QUFDNUMsUUFBTSxRQUFRLElBQUksV0FBVyxNQUFNO0FBQ25DLFdBQVMsSUFBSSxHQUFHLElBQUksT0FBTyxRQUFRLEtBQUs7QUFDcEMsVUFBTSxDQUFDLElBQUksT0FBTyxXQUFXLENBQUM7QUFBQSxFQUNsQztBQUNBLFNBQU87QUFDWDs7O0FDekJPLFNBQVMsMEJBQTBCO0FBQ3RDLFNBQU8sa0NBQWtDLFNBQVMsWUFBWSx3QkFBd0IsVUFDbEYsT0FBTyxXQUFXLHdCQUF3QixVQUFVO0FBQzVEO0FBS08sSUFBTSxvQ0FBb0M7QUFBQSxFQUM3QyxVQUFVLENBQUMs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
