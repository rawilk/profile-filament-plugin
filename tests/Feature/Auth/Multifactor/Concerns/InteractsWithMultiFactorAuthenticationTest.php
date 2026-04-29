<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('indicates if mfa is enabled', function () {
    expect($this->user->hasMultiFactorAuthenticationEnabled())->toBeFalse();

    $this->user->two_factor_enabled = true;

    expect($this->user->hasMultiFactorAuthenticationEnabled())->toBeTrue();
});

it('can toggle mfa status', function () {
    $this->user->toggleMultiFactorAuthenticationStatus(true);

    expect($this->user->refresh()->two_factor_enabled)->toBeTrue();

    $this->user->toggleMultiFactorAuthenticationStatus(false);

    expect($this->user->refresh()->two_factor_enabled)->toBeFalse();
});

it('gets a preferred mfa provider', function () {
    expect($this->user->getPreferredMfaProvider())->toBeNull();

    $this->user->preferred_mfa_provider = 'foo';

    expect($this->user->getPreferredMfaProvider())->toBe('foo');
});

it('updates a preferred mfa provider', function () {
    $this->user->setPreferredMfaProvider('foo');

    expect($this->user->refresh()->preferred_mfa_provider)->toBe('foo');
});
