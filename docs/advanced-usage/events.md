---
title: Events
sort: 1
---

## Introduction

There are several events that are dispatched from this package. Listening for these events can help you perform additional logging or send out security alerts to your users.

## Authentication

The following events are dispatched during the authentication process:

### PreparingAuthenticatedSession

The `Rawilk\ProfileFilament\Auth\Login\Events\PreparingAuthenticatedSession` event is dispatched during the login or MFA authentication process right before the session is regenerated. It has a property `user` for the authenticated user.

## Multi-Factor Authentication

The following events are dispatched for general MFA actions:

### MultiFactorAuthenticationChallengeWasPresented

The `Rawilk\ProfileFilament\Auth\Multifactor\Events\MultiFactorAuthenticationChallengeWasPresented` event is dispatched when a user attempts to login but is redirected to the MFA challenge form. It has a property `user` for the authenticated user.

### MultiFactorAuthenticationWasEnabled

The `Rawilk\ProfileFilament\Auth\Multifactor\Events\MultiFactorAuthenticationWasEnabled` event is dispatched when a user completely initially enables MFA on their account. It has a `user` property for the user MFA was enabled for.

### MultiFactorAuthenticationWasDisabled

The `Rawilk\ProfileFilament\Auth\Multifactor\Events\MultiFactorAuthenticationWasDisabled` event is dispatched when a user completely disables MFA on their account. This happens when there are no available MFA providers enabled on their account. It has a `user` property for the user MFA was disabled for.

## Authenticator Apps

The following events are dispatched for actions related to Authenticator Apps:

### AuthenticatorAppWasCreated

The `Rawilk\ProfileFilament\Auth\Multifactor\App\Events\AuthenticatorAppWasCreated` event is dispatched when a user registers a new authenticator app to their account. It has a `user` property for the user and an `authenticatorApp` property for the new authenticator app.

### AuthenticatorAppWasUpdated

The `Rawilk\ProfileFilament\Auth\Multifactor\App\Events\AuthenticatorAppWasUpdated` event is dispatched when a user updates an existing authenticator app's details (e.g., its name). It has an `authenticatorApp` property for the updated app and a `user` property for the owner.

### AuthenticatorAppWasUsed

The `Rawilk\ProfileFilament\Auth\Multifactor\App\Events\AuthenticatorAppWasUsed` event is dispatched when a user successfully uses an authenticator app to pass an MFA or sudo challenge. It has a `user` property for the user and an `authenticatorApp` property for the authenticator app that was used.

### AuthenticatorAppWasDeleted

The `Rawilk\ProfileFilament\Auth\Multifactor\App\Events\AuthenticatorAppWasDeleted` event is dispatched when a user removes an authenticator app from their account. It has a `user` property for the user and an `authenticatorApp` property for the authenticator app that was deleted.

## Security Keys

The following events are dispatched for actions related to Security Keys (WebAuthn):

### SecurityKeyWasCreated

The `Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Events\SecurityKeyWasCreated` event is dispatched when a user registers a new security key (WebAuthn) to their account. It has a `webauthnKey` property for the new key and a `user` property for the user.

### SecurityKeyWasUpdated

The `Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Events\SecurityKeyWasUpdated` event is dispatched when a user updates an existing security key's details. It has a `webauthnKey` property for the updated key and a `user` property for the owner.

### SecurityKeyWasUsed

The `Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Events\SecurityKeyWasUsed` event is dispatched when a user successfully uses a security key to pass an MFA or sudo challenge. It has `user`, `webauthnKey`, and `request` properties.

### SecurityKeyWasDeleted

The `Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Events\SecurityKeyWasDeleted` event is dispatched when a user removes a security key from their account. It has a `webauthnKey` property for the key that was deleted and a `user` property for the user.

## Recovery Codes

The following events are dispatched for actions related to MFA Recovery Codes:

### RecoveryCodesWereRegenerated

The `Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Events\RecoveryCodesWereRegenerated` event is dispatched when a user generates a new set of MFA recovery codes. It has a `user` property for the user who regenerated the codes.

### RecoveryCodeWasUsed

The `Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Events\RecoveryCodeWasUsed` event is dispatched when a user uses one of their recovery codes to pass an MFA challenge. It has a `user` property for the user.

## Email Authentication

The following events are dispatched for actions related to Email MFA:

### EmailAuthenticationWasEnabled

The `Rawilk\ProfileFilament\Auth\Multifactor\Email\Events\EmailAuthenticationWasEnabled` event is dispatched when a user enables email MFA on their account. It has a `user` property for the user.

### EmailAuthenticationWasDisabled

The `Rawilk\ProfileFilament\Auth\Multifactor\Email\Events\EmailAuthenticationWasDisabled` event is dispatched when a user disables email MFA on their account. It has a `user` property for the user.

## Sudo Mode

The following events are dispatched for actions related to Sudo Mode:

### SudoModeChallengeWasPresented

The `Rawilk\ProfileFilament\Auth\Sudo\Events\SudoModeChallengeWasPresented` event is dispatched when a user is prompted to enter sudo mode. It has `user` and `request` properties.

### SudoModeWasActivated

The `Rawilk\ProfileFilament\Auth\Sudo\Events\SudoModeWasActivated` event is dispatched when a user successfully enters sudo mode. It has `user` and `request` properties.

## Profile & Account

The following events are dispatched for actions related to user profile and account management:

### ProfileInformationWasUpdated

The `Rawilk\ProfileFilament\Events\Profile\ProfileInformationWasUpdated` event is dispatched when a user updates their profile information. It has a `user` property for the user.

### NewUserEmailVerified

The `Rawilk\ProfileFilament\Events\PendingUserEmails\NewUserEmailVerified` event is dispatched when a user verifies a new email address. It has a `user` property for the user and a `previousEmail` property containing the email address before the change.

### UserPasswordWasUpdated

The `Rawilk\ProfileFilament\Events\UserPasswordWasUpdated` event is dispatched when a user successfully updates their account password. It has a `user` property for the user.

### UserDeletedAccount

The `Rawilk\ProfileFilament\Events\UserDeletedAccount` event is dispatched when a user deletes their account. It has a `user` property for the user.
