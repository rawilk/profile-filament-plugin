<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorEnabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Events\MultiFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

beforeEach(function () {
    Event::fake();

    $this->action = MarkMultiFactorEnabledAction::class;
});

it('enables multi-factor authentication for a user', function () {
    /** @var HasMultiFactorAuthentication $user */
    $user = User::factory()->create();

    expect($user->hasMultiFactorAuthenticationEnabled())->toBeFalse();

    app($this->action)($user);

    expect($user->refresh())
        ->hasMultiFactorAuthenticationEnabled()->toBeTrue();
});

it('fires a MultiFactorAuthenticationWasEnabled event', function () {
    $user = User::factory()->create();

    app($this->action)($user);

    Event::assertDispatched(MultiFactorAuthenticationWasEnabled::class, function (MultiFactorAuthenticationWasEnabled $event) use ($user) {
        expect($event->user)->toBe($user);

        return true;
    });
});

it('does nothing if user already has multi-factor authentication enabled', function () {
    /** @var HasMultiFactorAuthentication $user */
    $user = User::factory()->withMfaEnabled()->create();

    expect($user->hasMultiFactorAuthenticationEnabled())->toBeTrue();

    app($this->action)($user);

    Event::assertNotDispatched(MultiFactorAuthenticationWasEnabled::class);
});

test('user must implement the HasMultiFactorAuthentication interface', function () {
    $user = new class extends Illuminate\Foundation\Auth\User
    {
    };

    app($this->action)($user);
})->throws(
    LogicException::class,
    HasMultiFactorAuthentication::class,
);
