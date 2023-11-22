<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Actions\AuthenticatorApps\ConfirmTwoFactorAppAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppAdded;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    config([
        'profile-filament.actions.mark_two_factor_enabled' => MarkTwoFactorEnabledAction::class,
    ]);
});

it('saves an authenticator app to a user and enables 2fa for the user', function () {
    Event::fake();
    $user = User::factory()->withoutMfa()->create();

    app(ConfirmTwoFactorAppAction::class)(
        user: $user,
        name: 'my app',
        secret: $secret = Str::random(32),
    );

    Event::assertDispatched(TwoFactorAppAdded::class);
    Event::assertDispatched(TwoFactorAuthenticationWasEnabled::class);

    $user->refresh();
    $authenticator = $user->authenticatorApps()->first();

    expect($user->two_factor_enabled)->toBeTrue()
        ->and($authenticator->name)->toBe('my app')
        ->and($authenticator->secret)->toBe($secret);
});

test('2fa is not enabled again for a user that already has it enabled', function () {
    Event::fake();
    $user = User::factory()->withMfa()->create();

    app(ConfirmTwoFactorAppAction::class)(
        user: $user,
        name: 'my app',
        secret: Str::random(32),
    );

    Event::assertDispatched(TwoFactorAppAdded::class);
    Event::assertNotDispatched(TwoFactorAuthenticationWasEnabled::class);
});
