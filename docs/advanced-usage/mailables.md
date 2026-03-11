---
title: Mailables
sort: 4
---

## Introduction

This package will send out emails for certain actions, such as when a user needs to verify a new email address. Since email and notification requirements can vary greatly for each application, we've opted to not send emails out for most actions. For any kind of mail not included here, you should listen to our [Events](/docs/profile-filament-plugin/{version}/advanced-usage/events) instead, and send out a notification accordingly.

## Pending Email Verification

The `PendingEmailVerificationMail` is sent out when a user initiates a change to their account email address. It will include a link to verify their new email address.

> {note} Your user model must implement the `MustVerifyNewEmail` interface for this mail to be sent out.

### Override View

You can publish the package's views, and modify the `mail/pending-email-verification.blade.php` view for this mail. The mailable will send the verification url as `$url`, and the new email address as `$email` to the view.

### Override Mailable

If you opt to use your own mailable class, you need to register it in the config. Your mailable should accept a `PendingUserEmail` model instance, and a string containing the `panelId` in its constructor.

```php
// config/profile-filament.php

'mail' => [
    'pending_email_verification' => YourCustomMailable::class,
],
```

## Pending Email Verified

The `PendingEmailVerifiedMail` is sent out when a user confirms a new email address. The email is sent as a security notification to their previous email address. This will allow them to revert their email address back if the change was done maliciously on their account.

### Override View

You can publish the package's views, and modify the `mail/email-updated.blade.php` view for this mail. The mailable will send the following variables to the view:

- `$maskedEmail`: An anonymized version of the new email address
- `$url`: A url to revert the email change back
- `$linkExpirationDays`: The amount of days the revert url link is valid for
- `$requestDetails`: HTML markup containing the IP address and date the request originated from

### Override Mailable

If you opt to use your own mailable class, you need to register it in the config. Your mailable should accept the following parameters in its constructor:

- `string $newEmail`: The new email address for the user
- `OldUserEmail $oldUserEmail`: A model instance containing the previous email address for the user - this is also where the email is sent to
- `?string $panelId`: The id of the current filament panel
- `?string $ip`: The IP address from the request
- `?DateTimeInterface $date`: The date the request was made

```php
// config/profile-filament.php

'mail' => [
    'pending_email_verified' => YourCustomMailable::class,
],
```
