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

If you wish to use any of the mfa or email verification features, you need to publish the migration files. You can publish and run the migrations with:

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

This is the minimum required to register the plugin, however there are many configurations you can do on a per-panel basis through the plugin. These will be covered throughout the documentation.

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

The plugin uses several tailwind classes that are not used by Filament, so a custom stylesheet is necessary. Nothing is required from you, since we register the stylesheet automatically for you, and load it on demand from any views in the package that require it. If the stylesheet isn't being loaded correctly, you may need to [publish it](https://filamentphp.com/docs/3.x/notifications/installation#upgrading).

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

**Note:** If your route's file does not have the `web` middleware applied to it, you will need to make sure it is applied to these routes. You can easily wrap our routes like this:

```php
Route::middleware(['web'])->group(function () {
    Route::webauthn();
});
```

## Pending Email Verification

This package offers the ability to force users to verify any changes to their email address before the system updates their actual email. This can be considered a security feature, as it requires a user to verify they own an email address before the system persists the change to their user account.

To implement this feature, you just need to add the `MustVerifyNewEmails` contract to your user model, and make sure you run the migrations found in the `create_pending_user_emails_table` migration from this package. See the [Migrations](#user-content-migrations) section for more information on migrations.

```php
use Illuminate\Foundation\Auth\User as BaseUser;

use Rawilk\ProfileFilament\Contracts\PendingUserEmail\MustVerifyNewEmail;

class User extends BaseUser implements MustVerifyNewEmail
{
    // ...
}
```

With that contract added to the User model, whenever a user updates their email address, we'll first send a verification email to their new email address first before we update their user record.

> {tip} This contract can be used alongside the `MustVerifyEmail` contract provided by Laravel. This package doesn't handle initial email verification functionality however; that is on you to implement yourself in your application.

### Pending Email Verification Configuration

There are also some configuration options you may define for pending email changes, such as how long a reversal link is valid for. We've provided some sensible defaults, however you are free to customize them in the config file:

```php
// config/profile-filament.php

'pending_email_changes' => [
    'revert_expiration' => DateInterval::createFromDateString('5 days'),
    'login_after_verification' => false,
    'login_remember' => true,
],
```

> {note} For security purposes, it's **not recommended** to automatically log a user in after they verify a new email address, so it's best to keep the `login_after_verification` value set to `false`.

## Two-Factor Authentication

In addition to the [migrations](#user-content-migrations) that need to be run, your user model needs to use the `TwoFactorAuthenticatable` trait:

```php
use Rawilk\ProfileFilament\Concerns\TwoFactorAuthenticatable;

class User extends BaseUser
{
    use TwoFactorAuthenticatable;
    // ...
}
```

## Actions

Many of the actions performed by this package can be customized in the configuration file, under the `actions` key. One action you may commonly find yourself needing to override, is the `delete_account` action. This action is called by the package when a user initiates a request to delete their account. Overriding this action class may be preferable and all you actually need to customize the account deletion process.

Here is an example of how you can accomplish this:

```php
namespace App\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Actions\DeleteAccountAction as BaseDeleteAccountAction;

class DeleteAccountAction extends BaseDeleteAccountAction
{
    public function __invoke(User $user)
    {
        $user->delete();

        // ...
    }
}
```

Now you just need to add the new action class to your config:

```php
// config/profile-filament.php

'actions' => [
    'delete_account' => \App\Actions\DeleteAccountAction::class,
    // ...
],
```

> {note} If you choose not to override the package's action class, you must implement the interface that the action class you're replacing implements.

## Models

You are free to override the package's models with your own model classes, however, you **must extend the model class you're replacing**. To use your own model classes, you may define them in the config file:

```php
// config/profile-filament.php

'models' => [
    'authenticator_app' => YourCustomClass::class,
    // ...
],
```

## Table Names

If you need to, you may use your own custom database table names by defining them in the config file:

```php
// config/profile-filament.php

'table_names' => [
    'authenticator_app' => 'your_custom_table_name',
    // ...
],
```

## Model Policies

We provide basic policies for some of the package's models. These policies should work for the majority of cases, but you are free to define your own policies in the config file:

```php
// config/profile-filament.php

'policies' => [
    'authenticator_app' => YourCustomPolicy::class,
    // ...
],
```
