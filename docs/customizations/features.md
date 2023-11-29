---
title: Features
sort: 2
---

## Introduction

The `Features` object allows you to customize which features are enabled for the plugin in a panel.

## Customizing the Features

To customize the available features, you may do so using the `features` method on the plugin object when you're registering it.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Features;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->features(
            Features::defaults()
                ->useSudoMode(false)
                ->twoFactorAuthentication(enabled: false)
        )
)
```

In the example above, both the sudo mode and two-factor authentication features will be disabled on the plugin in the panel you're using it on. If you have multiple panels and need to set the same feature flags for each panel, check out the [Global Settings](#user-content-global-settings) section below.

## Available Features

In this section, we'll cover the available feature flags that can be used to customize the behavior of the plugin for a panel.

### Update Password

By default, the update password form requires a user to enter their current password, a new password, and a password confirmation for their new password. You are free to customize this behavior depending on your application's requirements.

To remove the current password requirement, you can use the `requireCurrentPasswordToUpdatePassword` method:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->requireCurrentPasswordToUpdatePassword(false)
```

To remove the password confirmation requirement, you can use the `requirePasswordConfirmationToUpdatePassword` method:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->requirePasswordConfirmationToUpdatePassword(false)
```

To remove the update password form entirely, you may use the `updatePassword` method:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->updatePassword(false)
```

### Password Reset Link

In the [Update Password](#user-content-update-password) form, we show a "forgot password" link by default if your panel has password reset functionality enabled. If you want to remove the link from the form, you may use the `hidePasswordResetLink` method:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->hidePasswordResetLink()
```

### Multi-Factor Authentication

This package offers multiple options for multi-factor authentication (mfa) out-of-the-box, however they may not all be desirable in every application. You may use the `twoFactorAuthentication` method to toggle each part of mfa, or disable mfa entirely:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->twoFactorAuthentication(
        enabled: true,
        authenticatorApps: true,
        webauthn: true,
        passkeys: true,
    )
```

To disable mfa all-together, set the `enabled` parameter to `false`. Each parameter is optional, so you can pick and choose which ones you want to pass to the function.

### Passkeys

Passkeys offer an excellent alternative to the traditional username/password + two-factor authentication flow. However, if you wish to disable their usage on your panel, you may either pass `false` to the `passkeys` parameter in the `twoFactorAuthentication` method, as detailed above, or by using the `usePasskeys` method:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->usePasskeys(false)
```

> {note} Two-factor authentication must be enabled as well in order for passkeys to be allowed to use. This is so recovery codes can be used to recover a user's account in the case they lose access to a passkey.

### Sudo Mode

[Sudo mode](/docs/profile-filament-plugin/{version}/advanced-usage/sudo-mode) allows you to force a user to enter either their password or use a two-factor credential to confirm their identity before performing a sensitive action, such as updating their email address. The plugin enforces sudo mode on any sensitive action by default, however you may disable this by using the `useSudoMode` method:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->useSudoMode(false)
```

### Profile Info Form

The plugin offers a very basic profile information form that you can easily override and [swap out](/docs/profile-filament-plugin/{version}/customizations/page-customization#user-content-swap-components) for your own implementation. If you'd rather just remove the component entirely, you can use the `useDefaultProfileForm` method instead:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->useDefaultProfileForm(false)
```

### Update Email

On the account settings page, we provide a form for a user to update their email address, however you are free to disable the form using the `updateEmail` method:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->updateEmail(false)
```

### Delete Account

The plugin offers functionality for a user to delete their own account, however you may remove this form by using the `deleteAccount` method:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->deleteAccount(false)
```

### Session Manager

On the [Sessions](/docs/profile-filament-plugin/{version}/pages/sessions) profile page, the plugin offers functionality to manage a user's sessions. If you'd like to remove this component, you can use the `manageSessions` method:

```php
use Rawilk\ProfileFilament\Features;

Features::defaults()
    ->manageSessions(false)
```

## Global Settings

If you have multiple panels, it can be beneficial to configure some global defaults for the `Features` object. This can easily be done in a service provider. If you're familiar with Laravel's Password object, this should look familiar to you.

```php
use Rawilk\ProfileFilament\Features;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Features::defaults(function () {
            return Features::make()
                ->usePasskeys(false);
        });     
    }
}
```

With the example above, passkeys will be disabled on the plugin in every panel you register the plugin in. If you want to allow the passkeys feature in a specific panel only, you can easily override the global default you set above when you're registering the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Features;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->features(
            Features::defaults()->usePasskeys()
        )
)
```
