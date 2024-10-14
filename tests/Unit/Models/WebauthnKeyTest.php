<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
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

test('custom models can resolve the username differently', function () {
    $model = new class extends WebauthnKey
    {
        public static function getUsername(Authenticatable $user): string
        {
            return 'foo';
        }
    };

    config([
        'profile-filament.models.webauthn_key' => $model::class,
    ]);

    $user = User::factory()->make();

    expect(app(config('profile-filament.models.webauthn_key'))::getUsername($user))->toBe('foo');
});

it('can get the user handle (id) for a given user instance', function () {
    $user = User::factory()->make(['id' => 1]);

    expect(WebauthnKey::getUserHandle($user))->toBe('1');
});

test('custom models can resolve the user handle differently', function () {
    $model = new class extends WebauthnKey
    {
        public static function getUserHandle(Authenticatable $user): string
        {
            return $user->uuid;
        }
    };

    config([
        'profile-filament.models.webauthn_key' => $model::class,
    ]);

    $user = User::factory()->make(['uuid' => 'usr_1234']);

    expect(app(config('profile-filament.models.webauthn_key'))::getUserHandle($user))->toBe('usr_1234');
});

it('knows if it can be upgraded to a passkey', function (WebauthnKey $record, bool $canUpgrade) {
    expect($record->canUpgradeToPasskey())->toBe($canUpgrade);
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
    $records = WebauthnKey::factory()
        ->for(User::factory())
        ->count(5)
        ->sequence(
            ['is_passkey' => false],
            ['is_passkey' => true, 'attachment_type' => 'platform'],
        )
        ->create();

    $results = WebauthnKey::passkeys()->get();

    expect($results)
        ->toHaveCount(2)
        ->modelsMatchExactly($records->filter(fn (WebauthnKey $record) => $record->is_passkey));
});

it('can scope queries to non-passkeys only', function () {
    $records = WebauthnKey::factory()
        ->for(User::factory())
        ->count(5)
        ->sequence(
            ['is_passkey' => false],
            ['is_passkey' => true, 'attachment_type' => 'platform'],
        )
        ->create();

    $results = WebauthnKey::notPasskeys()->get();

    expect($results)
        ->toHaveCount(3)
        ->modelsMatchExactly($records->reject(fn (WebauthnKey $record) => $record->is_passkey));
});

it('renders when the key was last used in a time html tag', function () {
    Date::setTestNow('2024-01-01 10:00:00');

    $key = WebauthnKey::factory()->make(['last_used_at' => now()]);

    expect($key)
        ->last_used->toBeInstanceOf(HtmlString::class)
        ->and($key->last_used->toHtml())
        ->toContain('<time')
        ->toContain('datetime="2024-01-01T10:00:00Z"');
});

it('indicates if the key has never been used', function () {
    $key = WebauthnKey::factory()->make(['last_used_at' => null]);

    expect($key)
        ->last_used->toBeInstanceOf(HtmlString::class)
        ->and($key->last_used->toHtml())
        ->toContain(__('profile-filament::pages/security.mfa.method_never_used'))
        ->not->toContain('<time');
});

it('renders when the key was registered in a time html tag', function () {
    Date::setTestNow('2024-01-01 10:00:00');

    $key = WebauthnKey::factory()->make(['created_at' => now()]);

    expect($key)
        ->registered_at->toBeInstanceOf(HtmlString::class)
        ->and($key->registered_at->toHtml())
        ->toContain('<time')
        ->toContain('datetime="2024-01-01T10:00:00Z"');
});

it('encodes the credential id with base64_encode', function () {
    $key = WebauthnKey::factory()->for(User::factory())->create(['credential_id' => 'my_id']);

    expect($key->getRawOriginal('credential_id'))->toBe('bXlfaWQ');
});

it('can be created from a public key credential source', function () {
    $source = FakeWebauthn::publicKeyCredentialSource(encodeUserId: false);
    $user = User::factory()->create();

    $record = WebauthnKey::fromPublicKeyCredentialSource(
        source: $source,
        user: $user,
        keyName: 'my key',
        attachmentType: 'platform',
    );

    expect($record)
        ->name->toBe('my key')
        ->credential_id->toBe(FakeWebauthn::CREDENTIAL_ID)
        ->public_key_credential_source->toBeInstanceOf(PublicKeyCredentialSource::class);
});

test('user handle (id) must match to create key', function () {
    $source = FakeWebauthn::publicKeyCredentialSource(encodeUserId: false);

    $user = User::factory()->create(['id' => 9999]);

    WebauthnKey::fromPublicKeyCredentialSource(
        source: $source,
        user: $user,
        keyName: 'my key',
        attachmentType: 'platform',
    );
})->throws(WrongUserHandle::class);

it('can generate a public key credential source object from its data', function () {
    $record = WebauthnKey::factory()->for(User::factory(state: ['id' => 1]))->create([
        'credential_id' => FakeWebauthn::CREDENTIAL_ID,
    ]);

    expect($record->public_key_credential_source)
        ->toBeInstanceOf(PublicKeyCredentialSource::class)
        ->userHandle->toBe('1')
        ->publicKeyCredentialId->toBe(FakeWebauthn::CREDENTIAL_ID)
        ->and(WebauthnKey::byCredentialId(FakeWebauthn::CREDENTIAL_ID)->first())->toBe($record);
});
