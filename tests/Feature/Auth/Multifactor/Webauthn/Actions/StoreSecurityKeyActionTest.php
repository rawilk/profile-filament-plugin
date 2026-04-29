<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MarkMultiFactorEnabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\StoreSecurityKeyAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Events\SecurityKeyWasCreated;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Support\CredentialRecordConverter;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = User::factory()->create();

    config()->set('profile-filament.models.webauthn_key', WebauthnKey::class);

    $this->action = new class extends StoreSecurityKeyAction
    {
        protected function determinePublicKeyCredentialSource(
            string $securityKeyJson,
            string $securityKeyOptionsJson,
            string $hostName
        ): PublicKeyCredentialSource {
            return CredentialRecordConverter::toPublicKeyCredentialSource(PublicKeyCredentialSource::create(
                base64_decode('eHouz/Zi7+BmByHjJ/tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp/B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB+w==', true),
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                [],
                'none',
                EmptyTrustPath::create(),
                Uuid::fromString('00000000-0000-0000-0000-000000000000'),
                base64_decode('pQECAyYgASFYIJV56vRrFusoDf9hm3iDmllcxxXzzKyO9WruKw4kWx7zIlgg/nq63l8IMJcIdKDJcXRh9hoz0L+nVwP1Oxil3/oNQYs=', true),
                'foo',
                100,
            ));
        }
    };
});

it('stores a security key', function () {
    ($this->action)($this->user, '{}', '{}', 'localhost', ['name' => 'My Security Key']);

    assertDatabaseHas(WebauthnKey::class, [
        'user_id' => $this->user->getKey(),
        'name' => 'My Security Key',
    ]);
});

it('fires a SecurityKeyWasCreated event when a security key is stored', function () {
    Event::fake();

    ($this->action)($this->user, '{}', '{}', 'localhost', ['name' => 'My Security Key']);

    Event::assertDispatched(SecurityKeyWasCreated::class, function (SecurityKeyWasCreated $event) {
        expect($event->webauthnKey->name)->toBe('My Security Key')
            ->and($event->user)->toBe($this->user);

        return true;
    });
});

it('calls the MarkMultiFactorEnabledAction', function () {
    $this->mock(MarkMultiFactorEnabledAction::class)
        ->shouldReceive('__invoke')
        ->once()
        ->with($this->user);

    ($this->action)($this->user, '{}', '{}', 'localhost', ['name' => 'My Security Key']);
});
