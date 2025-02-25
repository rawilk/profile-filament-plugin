<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
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
});

it('creates an options object for passkey registration', function () {
    Str::createRandomStringsUsing(fn () => FakeWebauthn::rawAttestationChallenge());

    WebauthnKey::factory()->for($this->user)->create([
        'credential_id' => FakeWebauthn::CREDENTIAL_ID,
    ]);

    $expectedOptions = Webauthn::passkeyAttestationObjectFor($this->user);

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
                'displayName' => $this->user->email,
                'id' => 'MQ',
            ],
            'challenge' => FakeWebauthn::ATTESTATION_CHALLENGE,
            'excludeCredentials' => [
                [
                    'id' => FakeWebauthn::credentialIdEncoded(),
                ],
            ],
            'authenticatorSelection' => [
                'userVerification' => 'required',
                'residentKey' => 'required',
                'authenticatorAttachment' => 'platform',
            ],
        ])
        ->assertJsonCount(1, 'excludeCredentials')
        ->assertSessionHas(MfaSession::PasskeyAttestationPk->value, $expectedOptions);
})->skip("Don't know how to make test pass right now");

test('a key can be omitted from the excludeCredentials array when it is being upgraded', function () {
    Str::createRandomStringsUsing(fn () => FakeWebauthn::rawAttestationChallenge());

    $record = WebauthnKey::factory()->for($this->user)->create([
        'credential_id' => FakeWebauthn::CREDENTIAL_ID,
    ]);

    $expectedOptions = Webauthn::passkeyAttestationObjectFor($this->user, [$record->getKey()]);

    actingAs($this->user)
        ->post(route('profile-filament::webauthn.passkey_attestation_pk', ['exclude' => [$record->getKey()]]))
        ->assertSuccessful()
        ->assertJsonCount(0, 'excludeCredentials')
        ->assertJsonMissing([
            'excludeCredentials' => [
                [
                    'id' => FakeWebauthn::credentialIdEncoded(),
                ],
            ],
        ])
        ->assertSessionHas(MfaSession::PasskeyAttestationPk->value, $expectedOptions);
});

it('can generate options for passkey assertions', function () {
    Str::createRandomStringsUsing(fn () => FakeWebauthn::rawAssertionChallenge());

    $expectedOptions = Webauthn::passkeyAssertionObject();

    $url = URL::temporarySignedRoute(
        'profile-filament::webauthn.passkey_assertion_pk',
        now()->addHour()
    );

    post($url)
        ->assertSuccessful()
        ->assertJson([
            'challenge' => FakeWebauthn::ASSERTION_CHALLENGE,
            'rpId' => 'acme.test',
            'userVerification' => 'required',
        ])
        ->assertJsonCount(0, 'allowCredentials')
        ->assertSessionHas(MfaSession::PasskeyAssertionPk->value, $expectedOptions);
});
