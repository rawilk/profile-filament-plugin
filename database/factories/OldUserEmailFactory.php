<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Models\OldUserEmail;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Rawilk\ProfileFilament\Models\OldUserEmail>
 */
class OldUserEmailFactory extends Factory
{
    protected $model = OldUserEmail::class;

    public function definition(): array
    {
        return [
            'email' => fake()->safeEmail(),
            'token' => Str::random(),
        ];
    }
}
