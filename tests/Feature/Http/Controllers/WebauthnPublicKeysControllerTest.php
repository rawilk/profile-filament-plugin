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
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->user = User::factory()->withMfa()->create();

    Route::webauthn();
});

it('can generate a public key for an attestation for the authenticated user', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    WebauthnKey::factory()->for($this->user)->create();

    // Challenge will be the same as what's returned from controller since we're hard-coding
    // it in the callback above.
    $expectedPublicKey = WebauthnFacade::attestationObjectFor(
        $this->user->email,
        $this->user->id,
    );

    actingAs($this->user)
        ->post(route('profile-filament::webauthn.attestation_pk'))
        ->assertSuccessful()
        ->assertJson([
            'rp' => [
                'name' => 'Acme',
                'id' => 'acme.test',
            ],
            'user' => [
                'name' => $this->user->email,
                'id' => 'MQ', // '1'
            ],
            'challenge' => FakeWebauthn::ATTESTATION_CHALLENGE,
            'excludeCredentials' => [
                [
                    // Our factory uses this credential id by default
                    'id' => FakeWebauthn::CREDENTIAL_ID,
                ],
            ],
        ])
        ->assertSessionHas(MfaSession::AttestationPublicKey->value, serialize($expectedPublicKey));
});

it('can generate a public key for an assertion for a user', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAssertionChallenge());

    WebauthnKey::factory()->for($this->user)->create();

    $expectedPublicKey = WebauthnFacade::assertionObjectFor($this->user->id);

    $url = URL::signedRoute(
        'profile-filament::webauthn.assertion_pk',
        [
            'user' => $this->user->id,
            's' => MfaSession::AssertionPublicKey->value,
        ],
    );

    post($url)
        ->assertJson([
            'challenge' => FakeWebauthn::ASSERTION_CHALLENGE,
            'rpId' => 'acme.test',
            'allowCredentials' => [
                [
                    'id' => FakeWebauthn::CREDENTIAL_ID,
                ],
            ],
        ])
        ->assertSessionHas(MfaSession::AssertionPublicKey->value, serialize($expectedPublicKey));
});

it('does not generate public keys for invalid users', function () {
    $url = URL::signedRoute(
        'profile-filament::webauthn.assertion_pk',
        [
            'user' => 2,
            's' => MfaSession::AssertionPublicKey->value,
        ],
    );

    post($url)
        ->assertStatus(Response::HTTP_NOT_FOUND)
        ->assertSessionMissing(MfaSession::AssertionPublicKey->value);
});
