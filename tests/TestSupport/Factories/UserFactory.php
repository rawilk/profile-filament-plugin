<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\TestSupport\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'secret',
            'remember_token' => Str::random(10),
        ];
    }

    public function withMfaEnabled(): static
    {
        return $this->state([
            'two_factor_enabled' => true,
        ]);
    }

    public function withRecoveryCodes(?array $recoveryCodes): static
    {
        return $this->state([
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);
    }

    public function withEmailAuthentication(): static
    {
        return $this->state([
            'has_email_authentication' => true,
        ]);
    }

    public function unverified(): static
    {
        return $this->state([
            'email_verified_at' => null,
        ]);
    }
}
