<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->user = User::factory()->withMfa()->create(['id' => 1]);

    Route::webauthn();
});

afterEach(function () {
    Str::createRandomStringsNormally();
    Webauthn::generateChallengeWith(null);
});

it('creates an options object for webauthn registration', function () {
    Str::createRandomStringsUsing(fn () => FakeWebauthn::rawAttestationChallenge());

    WebauthnKey::factory()->for($this->user)->create([
        'credential_id' => FakeWebauthn::CREDENTIAL_ID,
    ]);

    $expectedOptions = Webauthn::attestationObjectFor($this->user);

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
                'displayName' => $this->user->email,
                'id' => 'MQ',
            ],
            'challenge' => FakeWebauthn::ATTESTATION_CHALLENGE,
            'excludeCredentials' => [
                [
                    'id' => FakeWebauthn::credentialIdEncoded(),
                ],
            ],
        ])
        ->assertJsonCount(1, 'excludeCredentials')
        ->assertSessionHas(MfaSession::AttestationPublicKey->value, $expectedOptions);
});

it('can generate options for authenticating a user with webauthn', function () {
    Str::createRandomStringsUsing(fn () => FakeWebauthn::rawAssertionChallenge());

    WebauthnKey::factory()->for($this->user)->create([
        'credential_id' => FakeWebauthn::CREDENTIAL_ID,
    ]);

    $expectedOptions = Webauthn::assertionObjectFor($this->user);

    $url = URL::temporarySignedRoute(
        'profile-filament::webauthn.assertion_pk',
        now()->addMinute(),
        [
            'user' => $this->user->getRouteKey(),
            's' => MfaSession::AssertionPublicKey->value,
        ],
    );

    post($url)
        ->assertJson([
            'challenge' => FakeWebauthn::ASSERTION_CHALLENGE,
            'rpId' => 'acme.test',
            'allowCredentials' => [
                [
                    'id' => FakeWebauthn::credentialIdEncoded(),
                ],
            ],
        ])
        ->assertJsonCount(1, 'allowCredentials')
        ->assertSessionHas(MfaSession::AssertionPublicKey->value, $expectedOptions);
});

it('generates a generic assertion option challenge for invalid users', function () {
    Webauthn::generateChallengeWith(fn () => FakeWebauthn::rawAssertionChallenge());

    Str::createRandomStringsUsing(fn () => 'QYsoXHwsgMPGt1ch');

    $expectedOptions = Webauthn::genericAssertion();

    $url = URL::temporarySignedRoute(
        'profile-filament::webauthn.assertion_pk',
        now()->addMinute(),
        [
            'user' => 'invalid',
            's' => MfaSession::AssertionPublicKey->value,
        ],
    );

    post($url)
        ->assertJson([
            'challenge' => FakeWebauthn::ASSERTION_CHALLENGE,
            'rpId' => 'acme.test',
            'allowCredentials' => [
                [
                    'id' => 'UVlzb1hId3NnTVBHdDFjaA', // base64 of our random string above
                ],
            ],
        ])
        ->assertJsonCount(1, 'allowCredentials')
        ->assertSessionHas(MfaSession::AssertionPublicKey->value, $expectedOptions);
});
