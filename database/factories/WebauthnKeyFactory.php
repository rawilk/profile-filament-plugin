<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Support\CredentialRecordConverter;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Support\Config;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Rawilk\ProfileFilament\Models\WebauthnKey>
 */
class WebauthnKeyFactory extends Factory
{
    protected $model = WebauthnKey::class;

    public function definition(): array
    {
        $authModel = Config::getAuthenticatableModel();

        return [
            'name' => fake()->word(),
            'user_id' => $authModel::factory(),
            'credential_id' => fake()->sentence(),
            'attachment_type' => 'platform',
            'is_passkey' => false,
            'data' => $this->dummyPublicKeyCredentialSource(),
        ];
    }

    public function notPasskey(): static
    {
        return $this->state(['is_passkey' => false]);
    }

    public function notUpgradeableToPasskey(): static
    {
        return $this->state([
            'is_passkey' => false,
            'attachment_type' => 'cross-platform',
        ]);
    }

    public function passkey(): static
    {
        return $this->state([
            'is_passkey' => true,
            'attachment_type' => 'platform',
        ]);
    }

    protected function dummyPublicKeyCredentialSource(): PublicKeyCredentialSource
    {
        return CredentialRecordConverter::toPublicKeyCredentialSource(PublicKeyCredentialSource::create(
            base64_decode(
                'eHouz/Zi7+BmByHjJ/tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp/B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB+w==',
                true
            ),
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            [],
            'none',
            EmptyTrustPath::create(),
            Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            base64_decode(
                'pQECAyYgASFYIJV56vRrFusoDf9hm3iDmllcxxXzzKyO9WruKw4kWx7zIlgg/nq63l8IMJcIdKDJcXRh9hoz0L+nVwP1Oxil3/oNQYs=',
                true
            ),
            'foo',
            100,
        ));
    }
}
