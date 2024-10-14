<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Crypt;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Support\RecoveryCode;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withMfa()->create(['id' => 1]);
});

afterEach(function () {
    RecoveryCode::generateCodesUsing(null);
});

it('can get recovery codes as an array', function () {
    expect($this->user->recoveryCodes())->toBeArray()->toHaveCount(8);
});

test('it generates a cache key for determining if the user has passkeys', function () {
    expect(User::hasPasskeysCacheKey($this->user))->toBe('user:1:has-passkeys');
});

it('can replace a recovery code', function () {
    $codes = [
        'one',
        'two',
        'three',
        'four',
    ];

    $this->user->fill([
        'two_factor_recovery_codes' => Crypt::encryptString(
            json_encode($codes)
        ),
    ])->save();

    expect($this->user->recoveryCodes())->toMatchArray($codes);

    RecoveryCode::generateCodesUsing(fn () => 'five');

    $this->user->replaceRecoveryCode('two');

    expect($this->user->recoveryCodes())
        ->toHaveCount(4)
        ->toMatchArray([
            'one',
            'five',
            'three',
            'four',
        ]);
});

it('has an authenticator apps relationship', function () {
    AuthenticatorApp::factory()->for($this->user)->count(2)->create();
    AuthenticatorApp::factory()->for(User::factory())->create();

    expect($this->user->authenticatorApps()->count())->toBe(2);

    $this->assertDatabaseCount(AuthenticatorApp::class, 3);
});

it('has a webauthn keys relationship', function () {
    WebauthnKey::factory()->for($this->user)->count(3)->create();
    WebauthnKey::factory()->for(User::factory())->count(2)->create();

    $this->assertDatabaseCount(WebauthnKey::class, 5);

    expect($this->user->webauthnKeys()->count())->toBe(3);
});

it('has a relationship for webauthn keys that excludes passkeys', function () {
    WebauthnKey::factory()->for($this->user)->notPasskey()->create();
    WebauthnKey::factory()->for($this->user)->passkey()->create();

    expect($this->user->nonPasskeyWebauthnKeys()->count())->toBe(1);
});

it('it has a relationship for strictly passkeys', function () {
    WebauthnKey::factory()->for($this->user)->notPasskey()->create();
    WebauthnKey::factory()->for($this->user)->passkey()->create();

    expect($this->user->passkeys()->count())->toBe(1);
});

it('can determine if the user has passkeys registered to them', function () {
    WebauthnKey::factory()->for($this->user)->notPasskey()->create();

    expect($this->user->hasPasskeys())->toBeFalse();

    WebauthnKey::factory()->for($this->user)->passkey()->create();

    cache()->forget(User::hasPasskeysCacheKey($this->user));

    expect($this->user->hasPasskeys())->toBeTrue();
});

it('can determine if the user has two factor authentication enabled on their account', function () {
    expect($this->user->two_factor_enabled)->toBeTrue();

    $this->user->update(['two_factor_enabled' => false]);

    expect($this->user->two_factor_enabled)->toBeFalse();
});
