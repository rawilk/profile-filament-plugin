<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorDisabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Events\MultiFactorAuthenticationWasDisabled;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

beforeEach(function () {
    Event::fake();

    $this->action = MarkMultiFactorDisabledAction::class;
});

it('disables multi-factor authentication for a user', function () {
    /** @var HasMultiFactorAuthentication $user */
    $user = User::factory()->withMfaEnabled()->create();

    expect($user->hasMultiFactorAuthenticationEnabled())->toBeTrue();

    app($this->action)($user);

    expect($user->hasMultiFactorAuthenticationEnabled())->toBeFalse();
});

it('fires a MultiFactorAuthenticationWasDisabled event', function () {
    $user = User::factory()->withMfaEnabled()->create();

    app($this->action)($user);

    Event::assertDispatched(MultiFactorAuthenticationWasDisabled::class, function (MultiFactorAuthenticationWasDisabled $event) use ($user) {
        expect($event->user)->toBe($user);

        return true;
    });
});

it('sets the user preferred mfa provider to null', function () {
    $user = User::factory()->withMfaEnabled()->create(['preferred_mfa_provider' => 'app']);

    expect($user)->preferred_mfa_provider->toBe('app');

    app($this->action)($user);

    expect($user)->preferred_mfa_provider->toBeNull();
});

it('removes recovery codes from the user', function () {
    /** @var Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\HasMultiFactorAuthenticationRecovery $user */
    $user = User::factory()->withMfaEnabled()->withRecoveryCodes(['one', 'two'])->create();

    expect($user->getAuthenticationRecoveryCodes())->toBeArray()->toHaveCount(2);

    app($this->action)($user);

    expect($user->refresh()->getAuthenticationRecoveryCodes())->toBeNull();
});

it('will not disable multi-factor authentication if the user still has at least one active provider registered to them', function (User $user) {
    app($this->action)($user);

    expect($user->hasMultiFactorAuthenticationEnabled())->toBeTrue();
})->with([
    'authenticator apps' => fn () => User::factory()
        ->withMfaEnabled()
        ->has(AuthenticatorApp::factory())
        ->create(),

    'webauthn' => fn () => User::factory()
        ->withMfaEnabled()
        ->has(WebauthnKey::factory(), 'securityKeys')
        ->create(),

    'email' => fn () => User::factory()
        ->withMfaEnabled()
        ->withEmailAuthentication()
        ->create(),
]);

test('user must implement the HasMultiFactorAuthentication interface to use the action', function () {
    $user = new class extends Illuminate\Foundation\Auth\User
    {
    };

    app($this->action)($user);
})->throws(
    LogicException::class,
    HasMultiFactorAuthentication::class,
);
