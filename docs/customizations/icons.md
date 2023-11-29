---
title: Icons
sort: 5
---

## Introduction

All the icons used in the views in this package are customizable with Filament's [icon alias](https://filamentphp.com/docs/3.x/support/icons#replacing-the-default-icons) system. Using icon aliases provides an easy way for you to customize the icons as needed.

```php
use Filament\Support\Facades\FilamentIcon;

FilamentIcon::register([
     // ...
]);
```

## Available Icon Aliases

Certain icons, like the trash can icon for delete actions are using Filament's icon alias names. If you can't find an alias for the icon you're looking for, there's a good chance it's using an alias created by Filament.

-   `profile-filament::passkey` - Passkey icon
-   `profile-filament::passkey-item-icon` - Passkey list item icon
-   `profile-filament::alert-dismiss` - Icon shown for the dismiss button in alerts
-   `profile-filament::pending-email-info` - Alert shown for pending email change
-   `profile-filament::help` - Icon shown beside certain help texts
-   `profile-filament::webauthn-error` - Next to error message shown when webauthn assertion/attestation fails
-   `mfa::totp` - Authenticator app icon
-   `mfa::webauthn` - Webauthn key icon
-   `mfa::recovery-codes` - Recovery codes icon
-   `mfa::recovery-codes-notice` - Warning icon shown next to warning message about storing your recovery codes in a safe place
-   `mfa::webauthn-unsupported` - Error message shown when webauthn is not supported by the browser
-   `mfa::recovery-codes.copy` - Copy recovery codes action button
-   `mfa::recovery-codes.download` - Download recovery codes action button
-   `mfa::recovery-codes.print` - Print recovery codes action button
-   `mfa::upgrade-to-passkey` - Passkey upgrade action button
-   `session::desktop` - Desktop device session list item
-   `session::mobile` - Mobile device session list item
-   `sudo::challenge` - Sudo challenge modal
