<?php

namespace Rawilk\ProfileFilament;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class ProfileFilamentPluginServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('profile-filament')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_profile-filament-plugin_table');
    }
}
