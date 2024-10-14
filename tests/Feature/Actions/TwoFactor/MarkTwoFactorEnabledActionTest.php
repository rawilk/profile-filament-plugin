<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

it('enables mfa for a user', function () {
    Event::fake();
    $user = User::factory()->withoutMfa()->create();

    expect($user->two_factor_enabled)->toBeFalse();

    app(MarkTwoFactorEnabledAction::class)($user);

    expect($user->refresh())
        ->two_factor_enabled->toBeTrue()
        ->recoveryCodes()->toHaveCount(8);

    Event::assertDispatched(function (TwoFactorAuthenticationWasEnabled $event) use ($user) {
        expect($event->user)->toBe($user);

        return true;
    });
});

it('does nothing if mfa is already enabled', function () {
    Event::fake();
    $user = User::factory()->withMfa()->create();

    $user->two_factor_recovery_codes = Crypt::encryptString(
        Collection::times(1, fn () => 'foo')->toJson(),
    );

    $user->save();

    app(MarkTwoFactorEnabledAction::class)($user);

    Event::assertNotDispatched(TwoFactorAuthenticationWasEnabled::class);

    $user->refresh();

    expect($user->recoveryCodes())->toHaveCount(1)
        ->and($user->recoveryCodes())->toMatchArray([
            'foo',
        ]);
});
