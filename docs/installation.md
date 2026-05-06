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

You can view the default configuration here: [https://github.com/rawilk/profile-filament-plugin/blob/{branch}/config/profile-filament.php](https://github.com/rawilk/profile-filament-plugin/blob/{branch}/config/profile-filament.php)

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
