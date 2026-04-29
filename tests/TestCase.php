<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use Orchestra\Testbench\TestCase as Orchestra;
use Rawilk\ProfileFilament\ProfileFilamentPluginServiceProvider;
use Rawilk\ProfileFilament\Tests\TestSupport\Filament\AdminPanelProvider;
use Rawilk\ProfileFilament\Tests\TestSupport\Filament\RequiresMfaPanelProvider;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;
use Rawilk\ProfileFilament\Tests\TestSupport\Services\InstantlyResolvingTimebox;

abstract class TestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = true;

    protected function setUp(): void
    {
        parent::setUp();

        Str::createRandomStringsUsing(fn () => 'fake-random-string');

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Rawilk\\ProfileFilament\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    public function getEnvironmentSetUp($app)
    {
        $app->bind(Timebox::class, InstantlyResolvingTimebox::class);

        config()->set('profile-filament.webauthn.relying_party.name', 'Acme');
        config()->set('profile-filament.webauthn.relying_party.name', 'https://acme.test');

        config()->set('auth.providers.users.model', User::class);
        config()->set('app.key', Encrypter::generateKey(config('app.cipher')));

        foreach ($this->getMigrations() as $migrationFile) {
            $migration = include $migrationFile;

            $migration->up();
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            ProfileFilamentPluginServiceProvider::class,
            AdminPanelProvider::class,
            RequiresMfaPanelProvider::class,
        ];
    }

    protected function getMigrations(): array
    {
        return [
            __DIR__ . '/../vendor/orchestra/testbench-core/laravel/migrations/0001_01_01_000000_testbench_create_users_table.php',
            __DIR__ . '/../database/migrations/add_two_factor_to_users_table.php.stub',
            __DIR__ . '/../database/migrations/create_authenticator_apps_table.php.stub',
            __DIR__ . '/../database/migrations/create_pending_user_emails_table.php.stub',
            __DIR__ . '/../database/migrations/create_webauthn_keys_table.php.stub',
            __DIR__ . '/TestSupport/Migrations/add_email_auth_to_users.php',
        ];
    }
}
