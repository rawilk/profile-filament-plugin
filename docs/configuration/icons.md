---
title: Icons
sort: 4
---

## Introduction

All the icons used throughout this package are customizable with Filament's [icon alias](https://filamentphp.com/docs/5.x/styling/icons#available-icon-aliases) system. Using icon aliases provides a convenient way to customize the icons as needed.

To make referencing the package's icons easier and more consistent, we use the `ProfileFilamentIcon` enum for each icon we use.

```php
use Filament\Support\Facades\FilamentIcon;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;

FilamentIcon::register([
    ProfileFilamentIcon::MfaWebauthn->value => 'pf-passkey',
]):
```

## Available icon aliases

Using class `Rawilk\ProfileFilament\Enums\ProfileFilamentIcon`

- `ProfileFilamentIcon::Help` - Icon shown beside certain help texts
- `ProfileFilamentIcon::LogoutSessionModalIcon` - Confirmation modal icon for log out session actions
- `ProfileFilamentIcon::MfaEmail` - Email authentication provider management schema icon. Also shown in the sudo challenge form
- `ProfileFilamentIcon::MfaTotp` - Authenticator app provider management schema icon. Also shown in the sudo challenge form
- `ProfileFilamentIcon::MfaRecoveryCodes` - Recovery codes provider management schema icon
- `ProfileFilamentIcon::MfaWebauthn` - Webauthn provider management schema icon. Also shown in the sudo challenge form
- `ProfileFilamentIcon::MfaWebauthnUnsupported` - Icon used in the error message when webauthn is not supported in the current browser
- `ProfileFilamentIcon::PendingEmailInfo` - Icon shows next to the text indicating that an email change is pending for the user
- `ProfileFilamentIcon::SessionDesktop` - Desktop session icon
- `ProfileFilamentIcon::SessionMobile` - Mobile session icon
- `ProfileFilamentIcon::SudoChallenge` - Icon shown above the sudo challenge form
