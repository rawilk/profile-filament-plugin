<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
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
            'secret' => Str::random(),
        ];
    }
}
