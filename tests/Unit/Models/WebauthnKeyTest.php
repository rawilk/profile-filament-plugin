<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\HtmlString;
use Rawilk\ProfileFilament\Exceptions\Webauthn\WrongUserHandle;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Webauthn\PublicKeyCredentialSource;

it('can get the username for a given user instance', function () {
    $user = User::factory()->make(['email' => 'email@example.com']);

    expect(WebauthnKey::getUsername($user))->toBe('email@example.com');
});

it('can get the user handle (id) for a given user instance', function () {
    $user = User::factory()->make(['id' => 1]);

    expect(WebauthnKey::getUserHandle($user))->toBe('1');
});

it('knows if it can be upgraded to a passkey', function (WebauthnKey $webauthnKey, bool $canBeUpgraded) {
    expect($webauthnKey->canUpgradeToPasskey())->toBe($canBeUpgraded);
})->with([
    'platform' => [fn () => WebauthnKey::factory()->make(['is_passkey' => false, 'attachment_type' => 'platform']), true],
    'cross platform' => [fn () => WebauthnKey::factory()->make(['is_passkey' => false, 'attachment_type' => 'cross-platform']), false],
    'passkey' => [fn () => WebauthnKey::factory()->make(['is_passkey' => true, 'attachment_type' => 'platform']), false],
]);

it('can find a credential by id', function () {
    $key1 = WebauthnKey::factory()->for(User::factory())->create(['credential_id' => 'my_id', 'name' => 'my credential']);
    WebauthnKey::factory()->for(User::factory())->create(['credential_id' => 'my_other_id', 'name' => 'my other credential']);

    $foundKeys = WebauthnKey::byCredentialId('my_id')->get();

    expect($foundKeys)->toHaveCount(1)
        ->first()
        ->toBe($key1)
        ->and($foundKeys->first()->name)->toBe('my credential');
});

it('can scope queries to passkeys only', function () {
    WebauthnKey::factory()->for(User::factory())->passkey()->count(2)->create();
    WebauthnKey::factory()->for(User::factory())->count(3)->create();

    $foundKeys = WebauthnKey::passkeys()->get();

    expect($foundKeys)->toHaveCount(2);
});

it('can scope queries to non-passkeys only', function () {
    WebauthnKey::factory()->for(User::factory())->passkey()->count(2)->create();
    WebauthnKey::factory()->for(User::factory())->count(3)->create();

    $foundKeys = WebauthnKey::notPasskeys()->get();

    expect($foundKeys)->toHaveCount(3);
});

it('renders when the key was last used in a time html tag', function () {
    Date::setTestNow('2023-01-01 10:00:00');

    $key = WebauthnKey::factory()->make(['last_used_at' => now()]);

    expect($key->last_used)->toBeInstanceOf(HtmlString::class)
        ->and($key->last_used->toHtml())
        ->toContain('<time')
        ->toContain('datetime="2023-01-01T10:00:00Z"');
});

it('indicates if the key has never been used', function () {
    $key = WebauthnKey::factory()->make(['last_used_at' => null]);

    expect($key->last_used)->toBeInstanceOf(HtmlString::class)
        ->and($key->last_used->toHtml())
        ->toContain(__('profile-filament::pages/security.mfa.method_never_used'))
        ->not->toContain('<time');
});

it('renders when the key was registered in a time html tag', function () {
    Date::setTestNow('2023-01-01 10:00:00');

    $key = WebauthnKey::factory()->make(['created_at' => now()]);

    expect($key->registered_at)->toBeInstanceOf(HtmlString::class)
        ->and($key->registered_at->toHtml())
        ->toContain('<time')
        ->toContain('datetime="2023-01-01T10:00:00Z"');
});

it('it encodes the credential id with base64_encode', function () {
    $key = WebauthnKey::factory()->for(User::factory())->create(['credential_id' => 'my_id']);

    expect($key->getRawOriginal('credential_id'))->toBe('bXlfaWQ');
});

it('can be created from a public key credential source', function () {
    $publicKeyCredentialSource = PublicKeyCredentialSource::createFromArray(FakeWebauthn::publicKey());
    $user = User::factory()->create();

    $webauthnKey = WebauthnKey::fromPublicKeyCredentialSource(
        source: $publicKeyCredentialSource,
        user: $user,
        keyName: 'my key',
        attachmentType: 'platform',
    );

    expect($webauthnKey)
        ->name->toBe('my key')
        ->credential_id->toBe(FakeWebauthn::rawCredentialId())
        ->public_key_credential_source->toBeInstanceOf(PublicKeyCredentialSource::class);
});

test('user handle (id) must match to create key', function () {
    $publicKeyCredentialSource = PublicKeyCredentialSource::createFromArray(FakeWebauthn::publicKey());
    User::factory()->create();
    $user2 = User::factory()->create();

    WebauthnKey::fromPublicKeyCredentialSource(
        source: $publicKeyCredentialSource,
        user: $user2,
        keyName: 'my key',
        attachmentType: 'platform',
    );
})->throws(WrongUserHandle::class);

it('can generate a public key credential source object from its public key', function () {
    $webauthnKey = WebauthnKey::factory()->for(User::factory())->create();

    expect($webauthnKey->public_key_credential_source)
        ->toBeInstanceOf(PublicKeyCredentialSource::class)
        ->userHandle->toBe('1')
        ->publicKeyCredentialId->toBe(FakeWebauthn::rawCredentialId());
});
