<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Rawilk\ProfileFilament\Models\WebauthnKey>
 */
class WebauthnKeyFactory extends Factory
{
    protected $model = WebauthnKey::class;

    public function definition(): array
    {
        return [
            'name' => fake()->ean13(),
            'credential_id' => Str::random(10),
            'attachment_type' => Arr::random(['platform', 'cross-platform']),
            'is_passkey' => false,
            'transports' => ['internal', 'hybrid'],
            'public_key' => FakeWebauthn::publicKey(),
        ];
    }

    public function notPasskey(): self
    {
        return $this->state(['is_passkey' => false]);
    }

    public function upgradeableToPasskey(): self
    {
        return $this->state([
            'is_passkey' => false,
            'attachment_type' => 'platform',
        ]);
    }

    public function passkey(): self
    {
        return $this->state([
            'is_passkey' => true,
            'attachment_type' => 'platform',
        ]);
    }
}
