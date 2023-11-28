# Filament Profile

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rawilk/profile-filament-plugin.svg?style=flat-square)](https://packagist.org/packages/rawilk/profile-filament-plugin)
![Tests](https://github.com/rawilk/profile-filament-plugin/workflows/Tests/badge.svg?style=flat-square)
[![Total Downloads](https://img.shields.io/packagist/dt/rawilk/profile-filament-plugin.svg?style=flat-square)](https://packagist.org/packages/rawilk/profile-filament-plugin)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/rawilk/profile-filament-plugin?style=flat-square)](https://packagist.org/packages/rawilk/profile-filament-plugin)
[![License](https://img.shields.io/github/license/rawilk/profile-filament-plugin?style=flat-square)](https://github.com/rawilk/profile-filament-plugin/blob/main/LICENSE.md)

![social image](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/social-image.png)

## Installation

You can install the package via composer:

```bash
composer require rawilk/profile-filament-plugin
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="profile-filament-plugin-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="profile-filament-plugin-config"
```

You can view the default configuration here: https://github.com/rawilk/profile-filament-plugin/blob/main/config/profile-filament-plugin.php

## Usage

```php
$profile-filament-plugin = new Rawilk\ProfileFilamentPlugin;
echo $profile-filament-plugin->echoPhrase('Hello, Rawilk!');
```

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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
