# Filament Profile

> **Warning:** While the plugin should be production ready, it is still in a pre-release stage. API and functionality are subject to change
> without a major version bump until a stable release is made.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rawilk/profile-filament-plugin.svg?style=flat-square)](https://packagist.org/packages/rawilk/profile-filament-plugin)
![Tests](https://github.com/rawilk/profile-filament-plugin/workflows/Tests/badge.svg?style=flat-square)
[![Total Downloads](https://img.shields.io/packagist/dt/rawilk/profile-filament-plugin.svg?style=flat-square)](https://packagist.org/packages/rawilk/profile-filament-plugin)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/rawilk/profile-filament-plugin?style=flat-square)](https://packagist.org/packages/rawilk/profile-filament-plugin)
[![License](https://img.shields.io/github/license/rawilk/profile-filament-plugin?style=flat-square)](https://github.com/rawilk/profile-filament-plugin/blob/main/LICENSE.md)

![social image](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/social-image.png)

This package provides a [Filament](https://filamentphp.com/) plugin for a user profile. The plugin acts as a starting point for your user profile, and provides
multi-factor authentication, password management, session management, and more. A lot of the boilerplate code that is required for these functionalities
is taken care of by this plugin.

## Installation

You can install the package via composer:

```bash
composer require rawilk/profile-filament-plugin
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="profile-filament-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="profile-filament-config"
```

You can view the default configuration here: https://github.com/rawilk/profile-filament-plugin/blob/main/config/profile-filament-plugin.php

## Usage

In a panel service provider, register the plugin:

```php
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

## Documentation

For comprehensive documentation, please visit: https://randallwilk.dev/docs/profile-filament-plugin

## Scripts

### Setup

For convenience, you can run the setup bin script for easy installation for local development.

```bash
./bin/setup.sh
```

### Formatting

Although formatting is done automatically via workflow, you can format php code locally before committing with a composer script:

```bash
composer format
```

### Release

When a new release is ready, the `./bin/release.sh` script should be run. This script will compile the front-end assets provided by the package.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security

Please review [my security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

-   [Randall Wilk](https://github.com/rawilk)
-   [All Contributors](../../contributors)

`Agent` service class for browser/device detection is derived from:

-   [Jenssegers/Agent](https://github.com/jenssegers/agent)
-   [Laravel Jetstream](https://github.com/laravel/jetstream)

## Alternatives

-   [Filament Breezy](https://github.com/jeffgreco13/filament-breezy)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
