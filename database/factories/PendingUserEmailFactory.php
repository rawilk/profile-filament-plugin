<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Password;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Support\Config;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Rawilk\ProfileFilament\Models\PendingUserEmail>
 */
class PendingUserEmailFactory extends Factory
{
    protected $model = PendingUserEmail::class;

    public function definition(): array
    {
        $authModel = Config::getAuthenticatableModel();

        return [
            'email' => fake()->safeEmail(),
            'user_id' => $authModel::factory(),
            'user_type' => app($authModel)->getMorphClass(),
            'token' => Password::broker()->getRepository()->createNewToken(),
        ];
    }
}
