<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PragmaRX\Google2FA\Google2FA;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Rawilk\ProfileFilament\Models\AuthenticatorApp>
 */
class AuthenticatorAppFactory extends Factory
{
    protected $model = AuthenticatorApp::class;

    public function definition(): array
    {
        return [
            'name' => fake()->ean13(),
            'secret' => app(Google2FA::class)->generateSecretKey(),
        ];
    }
}
