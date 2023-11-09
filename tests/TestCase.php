<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilamentPlugin\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Rawilk\ProfileFilamentPlugin\ProfileFilamentPluginServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Rawilk\\ProfileFilamentPlugin\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    public function getEnvironmentSetUp($app)
    {
        // include_once __DIR__ . '/../database/migrations/create_profile-filament-plugin_table.php.stub';
        // (new \CreatePackageTable())->up();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ProfileFilamentPluginServiceProvider::class,
        ];
    }
}
