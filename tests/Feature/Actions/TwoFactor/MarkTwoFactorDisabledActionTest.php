<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorDisabledAction;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasDisabled;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    Event::fake();

    $this->user = User::factory()->withMfa()->create();

    // Ensure authenticator apps and webauthn are enabled.
    getPanelFeatures()->twoFactorAuthentication(
        authenticatorApps: true,
        webauthn: true,
    );
});

it('disables 2fa for a user', function () {
    app(MarkTwoFactorDisabledAction::class)($this->user);

    Event::assertDispatched(TwoFactorAuthenticationWasDisabled::class);

    expect($this->user->fresh())
        ->two_factor_enabled->toBeFalse()
        ->two_factor_recovery_codes->toBeNull();
});

it('does not disable 2fa if user has authenticator apps registered to them', function () {
    AuthenticatorApp::factory()->for($this->user)->create();

    app(MarkTwoFactorDisabledAction::class)($this->user);

    Event::assertNotDispatched(TwoFactorAuthenticationWasDisabled::class);

    expect($this->user->fresh())
        ->two_factor_enabled->toBeTrue();
});

it('does not disable 2fa if user has webauthn keys registered to them', function () {
    WebauthnKey::factory()->for($this->user)->create();

    app(MarkTwoFactorDisabledAction::class)($this->user);

    Event::assertNotDispatched(TwoFactorAuthenticationWasDisabled::class);

    expect($this->user->fresh())
        ->two_factor_enabled->toBeTrue();
});
