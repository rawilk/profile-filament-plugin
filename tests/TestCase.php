<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Timebox;
use Orchestra\Testbench\TestCase as Orchestra;
use Rawilk\ProfileFilament\ProfileFilamentPluginServiceProvider;
use Rawilk\ProfileFilament\Tests\Fixtures\Filament\AdminPanelProvider;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Rawilk\ProfileFilament\Tests\Fixtures\Support\InstantlyResolvingTimebox;

class TestCase extends Orchestra
{
    use LazilyRefreshDatabase;

    protected $enablesPackageDiscoveries = true;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Rawilk\\ProfileFilament\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        // copy icon sets over to vendor directory
        if (File::exists(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/vendor/rawilk/profile-filament-plugin/resources/svg')) {
            return;
        }

        File::copyDirectory(
            directory: __DIR__ . '/../resources/svg',
            destination: __DIR__ . '/../vendor/orchestra/testbench-core/laravel/vendor/rawilk/profile-filament-plugin/resources/svg',
        );
    }

    public function getEnvironmentSetUp($app)
    {
        $app->bind(Timebox::class, InstantlyResolvingTimebox::class);

        // Use test user model for users provider
        $app['config']->set('auth.providers.users.model', User::class);

        // Webauthn config...
        $app['config']->set('profile-filament.webauthn.relying_party.name', 'Acme');
        $app['config']->set('profile-filament.webauthn.relying_party.id', 'https://acme.test');

        $migrations = [
            __DIR__ . '/Fixtures/database/migrations/create_users_table.php',
            __DIR__ . '/../database/migrations/add_two_factor_to_users_table.php.stub',
            __DIR__ . '/../database/migrations/create_authenticator_apps_table.php.stub',
            __DIR__ . '/../database/migrations/create_pending_user_emails_table.php.stub',
            __DIR__ . '/../database/migrations/create_webauthn_keys_table.php.stub',
        ];

        foreach ($migrations as $migration) {
            $migrationClass = require $migration;

            (new $migrationClass())->up();
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            ProfileFilamentPluginServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }
}
