<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\AuthenticatorApps\DeleteTwoFactorAppAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorDisabledAction;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppRemoved;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasDisabled;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    config([
        'profile-filament.actions.mark_two_factor_disabled' => MarkTwoFactorDisabledAction::class,
    ]);

    Event::fake();

    $this->user = User::factory()->withMfa()->create();
    $this->authenticator = AuthenticatorApp::factory()->for($this->user)->create();
});

it('deletes an authenticator app and disables 2fa for a user', function () {
    app(DeleteTwoFactorAppAction::class)($this->authenticator);

    Event::assertDispatched(function (TwoFactorAppRemoved $event) {
        return $event->user->is($this->user)
            && $event->authenticatorApp->is($this->authenticator);
    });
    Event::assertDispatched(TwoFactorAuthenticationWasDisabled::class);

    $this->assertDatabaseMissing(AuthenticatorApp::class, [
        'id' => $this->authenticator->id,
    ]);

    expect($this->user->fresh())
        ->two_factor_enabled->toBeFalse();
});

it('does not disable 2fa for a user if they have other authenticator apps registered', function () {
    $otherAuthenticator = AuthenticatorApp::factory()->for($this->user)->create();

    app(DeleteTwoFactorAppAction::class)($this->authenticator);

    Event::assertDispatched(function (TwoFactorAppRemoved $event) {
        return $event->authenticatorApp->is($this->authenticator);
    });
    Event::assertDispatchedTimes(TwoFactorAppRemoved::class, 1);
    Event::assertNotDispatched(TwoFactorAuthenticationWasDisabled::class);

    $this->assertDatabaseHas(AuthenticatorApp::class, [
        'id' => $otherAuthenticator->id,
    ]);

    expect($this->user->fresh())
        ->two_factor_enabled->toBeTrue();
});
