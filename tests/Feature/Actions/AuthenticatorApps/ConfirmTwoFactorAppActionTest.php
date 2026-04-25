<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorEnabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Actions\StoreAuthenticatorAppAction;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppAdded;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    config([
        'profile-filament.actions.mark_two_factor_enabled' => MarkMultiFactorEnabledAction::class,
        'profile-filament.models.authenticator_app' => AuthenticatorApp::class,
    ]);
});

it('saves an authenticator app for a user', function () {
    Event::fake();

    $user = User::factory()->withoutMfa()->create();

    app(StoreAuthenticatorAppAction::class)(
        user: $user,
        name: 'my app',
        secret: $secret = Str::random(32),
    );

    Event::assertDispatched(TwoFactorAppAdded::class);

    expect($user->authenticatorApps()->first())
        ->name->toBe('my app')
        ->secret->toBe($secret);
});

it('calls the action to enable mfa for a user', function () {
    $user = User::factory()->withoutMfa()->create();

    $this->mock(MarkMultiFactorEnabledAction::class)
        ->shouldReceive('__invoke')
        ->with($user)
        ->once();

    app(StoreAuthenticatorAppAction::class)(
        user: $user,
        name: 'my app',
        secret: Str::random(32),
    );
});
