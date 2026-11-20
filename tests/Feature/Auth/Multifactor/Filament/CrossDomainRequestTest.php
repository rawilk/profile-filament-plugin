<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

beforeEach(function () {
    Str::createRandomStringsNormally();
    $this->user = User::factory()->create();
    $this->context = 'mfa:webauthn';
    $this->nonce = ProfileFilament::generateWebauthnNonce($this->context . ':' . $this->user->getAuthIdentifier());
    $this->key = ProfileFilament::crossDomainChallengeCacheKey($this->user, $this->nonce);
    $this->response = [
        'userId' => Crypt::encrypt($this->user->getAuthIdentifier()),
        'nonce' => $this->nonce,
        'challenge' => Hash::make('completed-ceremony-challenge'),
    ];
});

it('accepts a completed ceremony only once', function () {
    cache()->put($this->key, 'completed-ceremony-challenge', 30);

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeTrue()
        ->and(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
});

it('rejects missing or malformed response fields', function (string $field, mixed $value) {
    cache()->put($this->key, 'completed-ceremony-challenge', 30);
    $this->response[$field] = $value;

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
})->with(['challenge', 'userId', 'nonce'])->with([[null], [''], [[]], [123], ['invalid']]);

it('rejects empty or malformed stored challenges', function (mixed $challenge) {
    cache()->put($this->key, $challenge, 30);
    $this->response['challenge'] = Hash::make('');

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
})->with([[''], [[]], [123]]);

it('rejects expired challenges', function () {
    cache()->put($this->key, 'completed-ceremony-challenge', 30);
    $this->travel(31)->seconds();

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
});

it('rejects a response from another session or ceremony', function () {
    cache()->put($this->key, 'completed-ceremony-challenge', 30);
    ProfileFilament::generateWebauthnNonce($this->context . ':' . $this->user->getAuthIdentifier());

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
});

it('rejects another provider or purpose', function (string $context) {
    cache()->put($this->key, 'completed-ceremony-challenge', 30);

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $context))->toBeFalse();
})->with(['sudo:webauthn', 'mfa:app']);

it('rejects a response for another user', function () {
    cache()->put($this->key, 'completed-ceremony-challenge', 30);
    $this->response['userId'] = Crypt::encrypt(User::factory()->create()->getAuthIdentifier());

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
});

it('requires the completion and nonce to belong to the same ceremony', function () {
    cache()->put($this->key, 'completed-ceremony-challenge', 30);
    $this->response['nonce'] = ProfileFilament::generateWebauthnNonce($this->context . ':' . $this->user->getAuthIdentifier());

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
});

it('rejects expired nonces even with a fresh completion', function () {
    $this->travel(16)->minutes();
    cache()->put($this->key, 'completed-ceremony-challenge', 30);

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
});

it('requires a nonce stored in the current session', function () {
    cache()->put($this->key, 'completed-ceremony-challenge', 30);
    WebauthnSession::Nonce->pull();

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
});

it('preserves context-free nonce verification for passkey login', function () {
    $nonce = ProfileFilament::generateWebauthnNonce();

    ProfileFilament::verifyWebauthnNonce($nonce);

    expect(WebauthnSession::Nonce->get())->toBeNull();

    ProfileFilament::verifyWebauthnNonce($nonce);
})->throws(RuntimeException::class, 'Invalid nonce');

it('rejects malformed stored nonces', function (mixed $nonce) {
    WebauthnSession::Nonce->put($nonce);
    $this->response['nonce'] = Crypt::encrypt($nonce);

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
})->with([[''], [[]], [123], ['missing-timestamp'], ['nonce:invalid-timestamp']]);

it('accepts integer and string authentication identifiers', function (int|string $id) {
    $user = new GenericUser(['id' => $id]);
    $nonce = ProfileFilament::generateWebauthnNonce($this->context . ':' . $id);
    cache()->put(ProfileFilament::crossDomainChallengeCacheKey($user, $nonce), 'completed-ceremony-challenge', 30);

    $response = [
        ...$this->response,
        'userId' => Crypt::encrypt($id),
        'nonce' => $nonce,
    ];

    expect(ProfileFilament::verifyCrossDomainRequest($response, $user, $this->context))->toBeTrue();
})->with([42, '42', 'usr_1234', '550e8400-e29b-41d4-a716-446655440000']);

it('rejects decrypted identifiers that are neither integers nor strings', function (mixed $id) {
    $user = new GenericUser(['id' => 1]);
    $nonce = ProfileFilament::generateWebauthnNonce($this->context . ':1');
    cache()->put(ProfileFilament::crossDomainChallengeCacheKey($user, $nonce), 'completed-ceremony-challenge', 30);

    $response = [
        ...$this->response,
        'userId' => Crypt::encrypt($id),
        'nonce' => $nonce,
    ];

    expect(ProfileFilament::verifyCrossDomainRequest($response, $user, $this->context))->toBeFalse();
})->with([[true], [false], [1.0], [null], [[]], [(object) ['id' => 1]]]);

it('rejects competing verification while the completion is being consumed', function () {
    cache()->put($this->key, 'completed-ceremony-challenge', 30);
    $lock = cache()->lock($this->key . ':consume', 10);
    expect($lock->get())->toBeTrue();

    try {
        expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
    } finally {
        $lock->release();
    }
});

it('rejects replay even if a concurrent request retained the original session state', function () {
    cache()->put($this->key, 'completed-ceremony-challenge', 30);
    $nonce = WebauthnSession::Nonce->get();
    $context = WebauthnSession::NonceContext->get();

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeTrue();

    WebauthnSession::Nonce->put($nonce);
    WebauthnSession::NonceContext->put($context);

    expect(ProfileFilament::verifyCrossDomainRequest($this->response, $this->user, $this->context))->toBeFalse();
});
