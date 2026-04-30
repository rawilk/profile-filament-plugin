---
title: Installation
sort: 3
---

## Installation

`profile-filament` can be installed via composer:

```bash
composer require rawilk/profile-filament-plugin
```

## Migrations

If you wish to use any of the multi-factor authentication or email verification features, you need to publish the migration files. You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="profile-filament-migrations"
php artisan migrate
```

## Configuration

You can publish the configuration file with:

```bash
php artisan vendor:publish --tag="profile-filament-config"
```

You can view the default configuration here: https://github.com/rawilk/profile-filament-plugin/blob/{branch}/config/profile-filament.php

> {tip} The config options found in this file are mostly global options. There are many options are available on the plugin class itself when registering it in a panel service provider. We will also detail below some of the global configurations you can make from the config file.

## Plugin Registration

To use any of the profile pages and features, you need to register the plugin with any filament panels you have in your application. Here is an example of registering the plugin with a panel:

```php
// app/Providers/Filament/AdminPanelProvider.php

use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugin(
                ProfileFilamentPlugin::make()
            );
    }
}
```

This is the minimum required to register the plugin; however, there are many configurations you can do on a per-panel basis through the plugin. These will be covered throughout the documentation.

## Translations

If you need to customize any of the translations, you can publish them with:

```bash
php artisan vendor:publish --tag="profile-filament-translations"
```

## Views

To override any of the views from this package, you can publish them with:

```bash
php artisan vendor:publish --tag="profile-filament-views"
```

## Styles

The plugin uses several tailwind classes that Filament does not use, so a custom stylesheet is necessary. Nothing is required from you, since we register the stylesheet automatically for you and load it on demand from any views in the package that require it. If the stylesheet isn't being loaded correctly, you may need to publish it.

A great way to ensure the latest assets are always loaded is to add the `filament:upgrade` script to your `composer.json` file.

```json
{
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi",
            "@php artisan filament:upgrade"
        ]
    }
}
```

## Routes

Most routes required for the package are registered automatically for you. However, the routes required for [Webauthn](/docs/profile-filament-plugin/{version}/advanced-usage/mfa#user-content-webauthn) public key generation are not registered automatically. If you plan on using webauthn functionality, you will need to register these routes using our route macro in one of your route files:

```php
// routes/web.php

Route::webauthn();
```

**Note:** If your route's file does not have the `web` middleware applied to it, you will need to make sure it is applied to these routes. You can wrap our routes like this:

```php
Route::middleware(['web'])->group(function () {
    Route::webauthn();
});
```

## Email Change Verification

This package offers the ability to force users to verify any changes to their email address before the system updates their actual email. This can be considered a security feature, as it requires a user to verify they own an email address before the system persists the change to their user account.

To implement this feature, you need to enable email change verification on the panel (not the plugin) and run the migrations found in the `create_pending_user_emails_table` migration from this package. See the [Migrations](#user-content-migrations) section for more information on migrations.

```php
// app/Providers/Filament/AdminPanelProvider.php

use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->emailChangeVerification()
            ->plugin(
                ProfileFilamentPlugin::make()
            );
    }
}
```

When this feature is enabled and a user submits a request to update their email address, we'll first send a verification email to their new email address first before we update their user record. We will also email their current email address to allow them to block the email change.

> {tip} This feature can (and should) be used alongside the `MustVerifyEmail` contract provided by Laravel. If you enable email verification on the panel, we will also provide a custom email verification prompt and controller.
