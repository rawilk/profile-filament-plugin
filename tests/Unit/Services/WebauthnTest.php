<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
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

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    $this->startSession();

    $this->service = new Webauthn(
        model: WebauthnKey::class,
        logger: new NullLogger,
    );

    Webauthn::generateChallengeWith(null);
    Str::createRandomStringsNormally();

    $this->user = User::factory()->withMfa()->create([
        'email' => 'email@example.com',
        'id' => 1,
    ]);

    config([
        'profile-filament.webauthn.authenticator_attachment' => AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
    ]);

    $this->webauthnKey = WebauthnKey::factory()->for($this->user)->create([
        'credential_id' => FakeWebauthn::CREDENTIAL_ID,
        'is_passkey' => false,
    ]);
});

it('can get a list of keys associated with a user', function () {
    $credentials = $this->service->getPublicKeyCredentialDescriptorsFor($this->user);

    expect($credentials)
        ->toHaveCount(1)
        ->toContainOnlyInstancesOf(PublicKeyCredentialDescriptor::class)
        ->and($credentials[0]->id)->toBe(FakeWebauthn::credentialIdEncoded());
});

it('can exclude certain keys', function () {
    $credentials = $this->service->getPublicKeyCredentialDescriptorsFor($this->user, exclude: [$this->webauthnKey->getKey()]);

    expect($credentials)->toHaveCount(0);
});

it('can generate options for an attestation', function () {
    Str::createRandomStringsUsing(fn (): string => '1LtnPPCb5iDHtW52GZPLbt3dfXU65Mo0');

    $options = $this->service->attestationObjectFor($this->user);

    expect($options)
        ->rp->id->toBe('acme.test')
        ->rp->name->toBe('Acme')
        ->challenge->toBe('1LtnPPCb5iDHtW52GZPLbt3dfXU65Mo0')
        ->user->name->toBe('email@example.com')
        ->user->id->toBe('1')
        ->user->displayName->toBe('email@example.com')
        ->authenticatorSelection->authenticatorAttachment->toBe(AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE)
        ->pubKeyCredParams->toBeArray()
        ->pubKeyCredParams->toContainOnlyInstancesOf(PublicKeyCredentialParameters::class)
        ->excludeCredentials->toHaveCount(1)
        ->and($options->excludeCredentials[0]->id)->toBe(FakeWebauthn::credentialIdEncoded());
});

it('can generate options for passkey attestations', function () {
    Str::createRandomStringsUsing(fn (): string => '1LtnPPCb5iDHtW52GZPLbt3dfXU65Mo0');

    $options = $this->service->passkeyAttestationObjectFor($this->user);

    expect($options)
        ->challenge->toBe('1LtnPPCb5iDHtW52GZPLbt3dfXU65Mo0')
        ->authenticatorSelection->userVerification->toBe(AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED)
        ->authenticatorSelection->residentKey->toBe(AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED)
        ->authenticatorSelection->authenticatorAttachment->toBe(AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM)
        ->excludeCredentials->toHaveCount(1);
});

it('can generate challenges with a custom callback', function () {
    Webauthn::generateChallengeWith(fn () => 'foo');

    $options = $this->service->attestationObjectFor($this->user);

    expect($options)
        ->challenge->toBe('foo');
});

it('can verify an attestation response', function () {
    Str::createRandomStringsUsing(fn (): string => FakeWebauthn::rawAttestationChallenge());

    $options = $this->service->attestationObjectFor($this->user);

    $source = $this->service->verifyAttestation(
        attestationResponse: FakeWebauthn::attestationResponse(),
        storedPublicKey: $options,
    );

    expect($source)
        ->publicKeyCredentialId->toBe(FakeWebauthn::rawCredentialId())
        ->userHandle->toBe('1');
});

test('challenge must match for attestations', function () {
    Str::createRandomStringsUsing(fn () => 'invalid');

    $options = $this->service->attestationObjectFor($this->user);

    $this->service->verifyAttestation(
        attestationResponse: FakeWebauthn::attestationResponse(),
        storedPublicKey: $options,
    );
})->throws(AttestationFailed::class);

it('can generate options for assertions', function () {
    Str::createRandomStringsUsing(fn (): string => '1LtnPPCb5iDHtW52GZPLbt3dfXU65Mo0');

    $options = $this->service->assertionObjectFor($this->user);

    expect($options)
        ->challenge->toBe('1LtnPPCb5iDHtW52GZPLbt3dfXU65Mo0')
        ->rpId->toBe('acme.test')
        ->allowCredentials->toHaveCount(1)
        ->and($options->allowCredentials[0]->id)->toBe(FakeWebauthn::credentialIdEncoded());
});

it('can generate assertion options for passkeys (userless)', function () {
    Str::createRandomStringsUsing(fn (): string => '1LtnPPCb5iDHtW52GZPLbt3dfXU65Mo0');

    $options = $this->service->passkeyAssertionObject();

    expect($options)
        ->challenge->toBe('1LtnPPCb5iDHtW52GZPLbt3dfXU65Mo0')
        ->rpId->toBe('acme.test')
        ->allowCredentials->toHaveCount(0)
        ->userVerification->toBe(AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED);
});

it('can verify an assertion', function () {
    Str::createRandomStringsUsing(fn () => FakeWebauthn::rawAssertionChallenge());

    //    $this->webauthnKey->update([
    //        'credential_id' => FakeWebauthn::rawCredentialId(),
    //    ]);

    DB::table('webauthn_keys')
        ->where('id', $this->webauthnKey->id)
        ->update([
            'credential_id' => FakeWebauthn::CREDENTIAL_ID,
        ]);

    /**
     * If we don't exclude the key from the allowed credentials list, the id hash
     * check performed by the WebAuthn library fails for some reason. Currently
     * not sure how get this to work in our tests...
     */
    $options = $this->service->assertionObjectFor($this->user, [$this->webauthnKey->getKey()]);

    $this->freezeSecond();

    ['authenticator' => $authenticator, 'publicKeyCredentialSource' => $publicKeyCredentialSource] = $this->service->verifyAssertion(
        user: $this->user,
        assertionResponse: FakeWebauthn::assertionResponse(),
        storedPublicKey: $options,
    );

    Event::assertDispatched(function (WebauthnKeyUsed $event) {
        expect($event->webauthnKey)->toBe($this->webauthnKey)
            ->and($event->user)->toBe($this->user);

        return true;
    });

    expect($authenticator)
        ->toBe($this->webauthnKey)
        ->last_used_at->toBe(now())
        ->and($publicKeyCredentialSource)
        ->toBeInstanceOf(PublicKeyCredentialSource::class);
});

test('non passkeys cannot be used for passkey assertions', function () {
    Str::createRandomStringsUsing(fn () => FakeWebauthn::rawAssertionChallenge());

    $this->webauthnKey->update([
        'credential_id' => FakeWebauthn::rawCredentialId(),
    ]);

    $options = $this->service->passkeyAssertionObject();

    $this->service->verifyAssertion(
        user: null,
        assertionResponse: FakeWebauthn::assertionResponse(),
        storedPublicKey: $options,
        requiresPasskey: true,
    );
})->throws(AssertionFailed::class, 'This key cannot be used for passkey authentication.');

test('a valid challenge must be presented for assertions', function () {
    Str::createRandomStringsUsing(fn () => 'invalid');

    $options = $this->service->assertionObjectFor($this->user);

    $this->service->verifyAssertion(
        user: $this->user,
        assertionResponse: FakeWebauthn::assertionResponse(),
        storedPublicKey: $options,
    );
})->throws(AssertionFailed::class);

it('can serialize public key credential sources for storage', function () {
    $source = FakeWebauthn::publicKeyCredentialSource(encodeUserId: false);

    $json = $this->service->serializePublicKeyCredentialSource($source);

    expect($json)->toBeJson()
        ->json()
        ->toHaveKey('publicKeyCredentialId', FakeWebauthn::credentialIdEncoded())
        ->toHaveKey('type', 'public-key')
        ->toHaveKey('userHandle', 'MQ');
});

it('can unserialize key data into a public key credential source', function () {
    $json = FakeWebauthn::serializedPublicKeyCredentialSource();

    $source = $this->service->unserializeKeyData($json);

    expect($source)
        ->publicKeyCredentialId->toBe(FakeWebauthn::credentialIdEncoded())
        ->userHandle->toBe('1');
});

it('can serialize public key credential creation options for a request response', function () {
    Str::createRandomStringsUsing(fn (): string => '1LtnPPCb5iDHtW52GZPLbt3dfXU65Mo0');

    $options = $this->service->attestationObjectFor($this->user);

    $result = $this->service->serializePublicKeyOptionsForRequest($options);

    expect($result['challenge'])->toBe('MUx0blBQQ2I1aURIdFc1MkdaUExidDNkZlhVNjVNbzA')
        ->and($result['rp']->name)->toBe('Acme')
        ->and($result['rp']->id)->toBe('acme.test')
        ->and($result['user']['id'])->toBe('MQ')
        ->and($result['user']['name'])->toBe('email@example.com')
        ->and($result['excludeCredentials'][0]->id)->toBe(FakeWebauthn::credentialIdEncoded());
});

it('can serialize public key credential request options for a request response', function () {
    Str::createRandomStringsUsing(fn (): string => '1LtnPPCb5iDHtW52GZPLbt3dfXU65Mo0');

    $options = $this->service->assertionObjectFor($this->user);

    $result = $this->service->serializePublicKeyOptionsForRequest($options);

    expect($result['challenge'])->toBe('MUx0blBQQ2I1aURIdFc1MkdaUExidDNkZlhVNjVNbzA')
        ->and($result['rpId'])->toBe('acme.test')
        ->and($result['allowCredentials'][0]->id)->toBe(FakeWebauthn::credentialIdEncoded())
        ->and($result)->not->toHaveKey('user');
});
