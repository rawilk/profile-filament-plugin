<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Psr\Log\NullLogger;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUsed;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AssertionFailed;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AttestationFailed;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Services\Webauthn;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialSource;

beforeEach(function () {
    Event::fake();
    $this->startSession();

    $this->service = new Webauthn(
        model: WebauthnKey::class,
        logger: new NullLogger,
    );

    Webauthn::generateChallengeWith(null);

    $this->user = User::factory()->withMfa()->create([
        'email' => 'email@example.com',
    ]);

    config([
        'profile-filament.webauthn.authenticator_attachment' => AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
    ]);

    $this->webauthnKey = WebauthnKey::factory()->for($this->user)->create([
        'credential_id' => FakeWebauthn::rawCredentialId(),
    ]);
});

it('can get a list of keys associated with a user', function () {
    $credentials = $this->service->getPublicKeyCredentialDescriptorsFor($this->user->id);

    expect($credentials)
        ->toHaveCount(1)
        ->toContainOnlyInstancesOf(PublicKeyCredentialDescriptor::class);
});

test('certain keys can be excluded in the list of user keys', function () {
    $credentials = $this->service->getPublicKeyCredentialDescriptorsFor($this->user->id, [$this->webauthnKey->id]);

    expect($credentials)->toHaveCount(0);
});

it('can generate an attestation public key', function () {
    $publicKey = $this->service->attestationObjectFor($this->user->email, $this->user->id);

    expect($publicKey)
        ->rp->id->toBe('acme.test')
        ->rp->name->toBe('Acme')
        ->user->name->toBe('email@example.com')
        ->user->id->toBe('1')
        ->user->displayName->toBe('email@example.com')
        ->authenticatorSelection->authenticatorAttachment->toBe(AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE)
        ->pubKeyCredParams->toBeArray()
        ->pubKeyCredParams->toContainOnlyInstancesOf(PublicKeyCredentialParameters::class)
        ->excludeCredentials->toHaveCount(1);
});

it('can generate an attestation public key for passkeys', function () {
    $publicKey = $this->service->passkeyAttestationObjectFor($this->user->email, $this->user->id);

    expect($publicKey)
        ->authenticatorSelection->userVerification->toBe(AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED)
        ->authenticatorSelection->residentKey->toBe(AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED)
        ->authenticatorSelection->authenticatorAttachment->toBe(AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM);
});

it('can verify an attestation', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    $publicKey = $this->service->attestationObjectFor($this->user->email, $this->user->id);

    $publicKeyCredentialSource = $this->service->verifyAttestation(
        attestationResponse: FakeWebauthn::attestationResponse(),
        storedPublicKey: $publicKey,
    );

    expect($publicKeyCredentialSource)
        ->publicKeyCredentialId->toBe(FakeWebauthn::rawCredentialId())
        ->userHandle->toBe('1');
});

test('challenge must match for attestations', function () {
    // The challenge in the public key will not match our hard-coded challenge in FakeWebauthn.
    $publicKey = $this->service->attestationObjectFor($this->user->email, $this->user->id);

    $this->service->verifyAttestation(
        attestationResponse: FakeWebauthn::attestationResponse(),
        storedPublicKey: $publicKey,
    );
})->throws(AttestationFailed::class);

it('can generate an assertion public key', function () {
    $publicKey = $this->service->assertionObjectFor($this->user->id);

    expect($publicKey)
        ->rpId->toBe('acme.test')
        ->allowCredentials->toHaveCount(1)
        ->and(strlen($publicKey->challenge))->toBe(32);
});

it('can generate an assertion public key for passkeys (userless)', function () {
    $publicKey = $this->service->passkeyAssertionObject();

    expect($publicKey)
        ->rpId->toBe('acme.test')
        ->allowCredentials->toHaveCount(0)
        ->and(strlen($publicKey->challenge))->toBe(32);
});

it('can verify an assertion', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAssertionChallenge());

    $publicKey = $this->service->assertionObjectFor($this->user->id);

    Date::setTestNow('2023-01-01 10:00:00');

    ['authenticator' => $authenticator, 'publicKeyCredentialSource' => $publicKeyCredentialSource] = $this->service->verifyAssertion(
        user: $this->user,
        assertionResponse: FakeWebauthn::assertionResponse(),
        storedPublicKey: $publicKey,
    );

    Event::assertDispatched(function (WebauthnKeyUsed $event) {
        return $event->webauthnKey->is($this->webauthnKey)
            && $event->user->is($this->user);
    });

    expect($authenticator)
        ->toBe($this->webauthnKey)
        ->last_used_at->toDateTimeString()->toBe('2023-01-01 10:00:00')
        ->and($publicKeyCredentialSource)
        ->toBeInstanceOf(PublicKeyCredentialSource::class)
        ->publicKeyCredentialId->toBe(FakeWebauthn::rawCredentialId());
});

test('non passkeys cannot be used for passkey assertions', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAssertionChallenge());

    $publicKey = $this->service->passkeyAssertionObject();

    $this->webauthnKey->update(['is_passkey' => false]);

    $this->service->verifyAssertion(
        user: null,
        assertionResponse: FakeWebauthn::assertionResponse(),
        storedPublicKey: $publicKey,
        requiresPasskey: true,
    );
})->throws(AssertionFailed::class, 'This key cannot be used for passkey authentication.');

test('a valid challenge must be presented', function () {
    $publicKey = $this->service->assertionObjectFor($this->user->id);

    $this->service->verifyAssertion(
        user: $this->user,
        assertionResponse: FakeWebauthn::assertionResponse(),
        storedPublicKey: $publicKey,
    );
})->throws(AssertionFailed::class);
