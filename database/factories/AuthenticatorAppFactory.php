<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Support\Config;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Rawilk\ProfileFilament\Models\AuthenticatorApp>
 */
class AuthenticatorAppFactory extends Factory
{
    protected $model = AuthenticatorApp::class;

    public function definition(): array
    {
        $authModel = Config::getAuthenticatableModel();

        return [
            'name' => fake()->word(),
            'user_id' => $authModel::factory(),
            'secret' => AppAuthenticationProvider::make()->generateSecret(),
        ];
    }
}
