<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Webauthn as WebauthnFacade;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Services\Webauthn;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->user = User::factory()->withMfa()->create();

    Route::webauthn();
});

it('generates a public key for an attestation for the authenticated user', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    WebauthnKey::factory()->for($this->user)->create();

    $expectedPublicKey = WebauthnFacade::passkeyAttestationObjectFor(
        username: $this->user->email,
        userId: $this->user->id,
    );

    actingAs($this->user)
        ->post(route('profile-filament::webauthn.passkey_attestation_pk'))
        ->assertSuccessful()
        ->assertJson([
            'rp' => [
                'name' => 'Acme',
                'id' => 'acme.test',
            ],
            'user' => [
                'name' => $this->user->email,
                'id' => 'MQ',
            ],
            'challenge' => FakeWebauthn::ATTESTATION_CHALLENGE,
            'excludeCredentials' => [
                [
                    'id' => FakeWebauthn::CREDENTIAL_ID,
                ],
            ],
            'authenticatorSelection' => [
                'userVerification' => 'required',
                'residentKey' => 'required',
                'authenticatorAttachment' => 'platform',
            ],
        ])
        ->assertJsonCount(1, 'excludeCredentials')
        ->assertSessionHas(MfaSession::PasskeyAttestationPk->value, serialize($expectedPublicKey));
});

test('certain registered keys can be omitted from the excludeCredentials array in the public key', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    WebauthnKey::factory()->for($this->user)->create();

    $expectedPublicKey = WebauthnFacade::passkeyAttestationObjectFor(
        username: $this->user->email,
        userId: $this->user->id,
        excludeCredentials: [1],
    );

    actingAs($this->user)
        ->post(route('profile-filament::webauthn.passkey_attestation_pk', ['exclude' => [1]]))
        ->assertSuccessful()
        ->assertJsonMissingPath('excludeCredentials')
        ->assertSessionHas(MfaSession::PasskeyAttestationPk->value, serialize($expectedPublicKey));
});

it('can generate a public key for passkey assertions', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAssertionChallenge());

    $expectedPublicKey = WebauthnFacade::passkeyAssertionObject();

    $url = URL::signedRoute(
        'profile-filament::webauthn.passkey_assertion_pk',
        [
            't' => now()->unix(),
        ],
    );

    post($url)
        ->assertSuccessful()
        ->assertJson([
            'challenge' => FakeWebauthn::ASSERTION_CHALLENGE,
            'rpId' => 'acme.test',
            'userVerification' => 'required',
        ])
        ->assertJsonMissingPath('allowCredentials')
        ->assertSessionHas(MfaSession::PasskeyAssertionPk->value, serialize($expectedPublicKey));
});
