---
title: Events
sort: 3
---

## Introduction

There are a lot of events that are dispatched from this package. Listening for these events can help you perform additional logging or send out security alerts to your users.

## Available Events

The following events are dispatched from the package. The base namespace for each event is `Rawilk\ProfileFilament\Events`.

### TwoFactorAppAdded

**Namespace**: `AuthenticatorApps\TwoFactorAppAdded`

This event is dispatched when an authenticator (totp) app is registered for a user. It will receive the following parameters:

-   `$user`: The authenticated user
-   `$authenticatorApp`: The `AuthenticatorApp` model being registered

### TwoFactorAppRemoved

**Namespace**: `AuthenticatorApps\TwoFactorAppRemoved`

This event is dispatched when an authenticator (totp) app is deleted. It will receive the following parameters:

-   `$user`: The authenticated user
-   `$authenticatorApp`: The `AuthenticatorApp` model being deleted

### TwoFactorAppUpdated

**Namespace**: `AuthenticatorApps\TwoFactorAppUpdated`

This event is dispatched when an authenticator app's name is updated. It will receive the following parameters:

-   `$user`: The authenticated user
-   `$authenticatorApp`: The `AuthenticatorApp` model being updated
-

### TwoFactorAppUsed

**Namespace**: `AuthenticatorApps\TwoFactorAppUsed`

This event is dispatched when an authenticator (totp) app is used to verify a user's identity. It will receive the following parameters:

-   `$user`: The user the app belongs to
-   `$authenticatorApp`: The `AuthenticatorApp` model being used

### PasskeyDeleted

**Namespace**: `Passkeys\PasskeyDeleted`

This event is dispatched when user deletes a passkey. It will receive the following parameters:

-   `$passkey`: The `WebauthnKey` passkey model being deleted
-   `$user`: The user the passkey belongs to

### PasskeyRegistered

**Namespace**: `Passkeys\PasskeyRegistered`

This event is dispatched when user registers a passkey. It will receive the following parameters:

-   `$passkey`: The `WebauthnKey` passkey model being registered
-   `$user`: The user the passkey belongs to

### PasskeyUpdated

**Namespace**: `Passkeys\PasskeyUpdated`

This event is dispatched when user updates a passkey's name. It will receive the following parameters:

-   `$passkey`: The `WebauthnKey` passkey model being updated
-   `$user`: The user the passkey belongs to

### EmailAddressReverted

**Namespace**: `PendingUserEmails\EmailAddressReverted`

This event is dispatched when user clicks the revert url link in the Pending Email Verified email. It will receive the following parameters:

-   `$user`: The user reverting their email
-   `$revertedFrom`: The email address that was cancelled
-   `$revertedTo`: The email address the user will now use

### NewUserEmailVerified

**Namespace**: `PendingUserEmails\NewUserEmailVerified`

This event is dispatched when a user verifies an email address change. It will receive the following parameters:

-   `$user`: The user that verified their new email address
-   `$previousEmail`: The user's previous email address

### ProfileInformationUpdated

**Namespace**: `Profile\ProfileInformationUpdated`

This event is dispatched when a user updates their profile information. It will receive the following parameters:

-   `$user`: The authenticated user

### SudoModeActivated

**Namespace**: `Sudo\SudoModeActivated`

This event is dispatched when user successfully confirms their identity for sudo mode. It will receive the following parameters:

-   `$user`: The user that entered sudo mode
-   `$request`: The current request object

### SudoModeChallenged

**Namespace**: `Sudo\SudoModeChallenged`

This event is dispatched when user is shown a sudo mode prompt. It will receive the following parameters:

-   `$user`: The user that was shown the sudo prompt
-   `$request`: The current request object

### WebauthnKeyDeleted

**Namespace**: `Webauthn\WebauthnKeyDeleted`

This event is dispatched when user deletes a webauthn key from their account. It will receive the following parameters:

-   `$webauthnKey`: The webauthn key being deleted
-   `$user`: The user the key belongs to

### WebauthnKeyRegistered

**Namespace**: `Webauthn\WebauthnKeyRegistered`

This event is dispatched when user registers a new webauthn key to their account. It will receive the following parameters:

-   `$webauthnKey`: The webauthn key being registered
-   `$user`: The user the key belongs to

### WebauthnKeyUpdated

**Namespace**: `Webauthn\WebauthnKeyUpdated`

This event is dispatched when user deletes a webauthn updates a webauthn key's name. It will receive the following parameters:

-   `$webauthnKey`: The webauthn key being updated
-   `$user`: The user the key belongs to

### WebauthnKeyUpgradeToPasskey

**Namespace**: `Webauthn\WebauthnKeyUpgradeToPasskey`

This event is dispatched when user upgrades a webauthn key to a passkey. It will receive the following parameters:

-   `$user`: The user the key belongs to
-   `$passkey`: The newly registered passkey
-   `$upgradedFrom`: The webauthn key that was upgraded (and deleted)

### WebauthnKeyUsed

**Namespace**: `Webauthn\WebauthnKeyUsed`

This event is dispatched when user uses a webauthn key to verify their identity. It will receive the following parameters:

-   `$user`: The user the key belongs to
-   `$webauthnKey`: The webauthn key being used

> {tip} This will also be dispatched when for a passkey when it is used.

### RecoveryCodeReplaced

**Namespace**: `RecoveryCodeReplaced`

This event is dispatched when user uses a recovery code and it is replaced. It will receive the following parameters:

-   `$user`: The user using a recovery code
-   `$oldCode`: The code that was used
-   `$newCode`: The new code being assigned to the user

### RecoveryCodesRegenerated

**Namespace**: `RecoveryCodesRegenerated`

This event is dispatched when user regenerates their recovery codes. It will receive the following parameters:

-   `$user`: The user regenerating recovery codes

### RecoveryCodesViewed

**Namespace**: `RecoveryCodesViewed`

This event is dispatched when user views their recovery codes. It will receive the following parameters:

-   `$user`: The user viewing their recovery codes

### TwoFactorAuthenticationChallenged

**Namespace**: `TwoFactorAuthenticationChallenged`

This event is dispatched when a user is forced to verify their identity with two-factor authentication. It will receive the following parameters:

-   `$user`: The user being challenged

### TwoFactorAuthenticationWasDisabled

**Namespace**: `TwoFactorAuthenticationWasDisabled`

This event is dispatched when a user removes every mfa method on their account and has mfa disabled. It will receive the following parameters:

-   `$user`: The user disabling mfa

### TwoFactorAuthenticationWasEnabled

**Namespace**: `TwoFactorAuthenticationWasEnabled`

This event is dispatched when a user first adds a second factor of authentication and mfa is enabled on their account. It will receive the following parameters:

-   `$user`: The user enabling mfa

### UserDeletedAccount

**Namespace**: `UserDeletedAccount`

This event is dispatched when a user deletes their own account. It will receive the following parameters:

-   `$user`: The user being deleted

### UserPasswordWasUpdated

**Namespace**: `UserPasswordWasUpdated`

This event is dispatched when a user updates their password. It will receive the following parameters:

-   `$user`: The user updating their password
