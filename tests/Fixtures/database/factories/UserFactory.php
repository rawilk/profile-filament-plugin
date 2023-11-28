<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\Fixtures\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Rawilk\ProfileFilament\Support\RecoveryCode;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'secret',
            'email_verified_at' => null,
        ];
    }

    public function verified(): self
    {
        return $this->state(['email_verified_at' => fake()->dateTime()]);
    }

    public function notVerified(): self
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function withoutMfa(): self
    {
        return $this->state([
            'two_factor_enabled' => false,
            'two_factor_recovery_codes' => null,
        ]);
    }

    public function withMfa(): self
    {
        return $this->state([
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => Crypt::encryptString(
                Collection::times(8, fn () => RecoveryCode::generate())->toJson()
            ),
        ]);
    }
}
