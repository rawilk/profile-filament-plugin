<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorDisabledAction;
use Rawilk\ProfileFilament\Actions\Webauthn\DeleteWebauthnKeyAction;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasDisabled;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyDeleted;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    config([
        'profile-filament.actions.mark_two_factor_disabled' => MarkTwoFactorDisabledAction::class,
    ]);

    Event::fake();

    $this->user = User::factory()->withMfa()->create();

    $this->webauthnKey = WebauthnKey::factory()->for($this->user)->create();
});

it('deletes a webauthn key', function () {
    app(DeleteWebauthnKeyAction::class)($this->webauthnKey);

    Event::assertDispatched(function (WebauthnKeyDeleted $event) {
        return $event->webauthnKey->is($this->webauthnKey)
            && $event->user->is($this->user);
    });

    Event::assertDispatched(TwoFactorAuthenticationWasDisabled::class);

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'id' => $this->webauthnKey->id,
    ]);

    expect($this->user->refresh())
        ->two_factor_enabled->toBeFalse();
});

it('does not disable 2fa for a user if they have other webauthn keys registered', function () {
    $otherKey = WebauthnKey::factory()->for($this->user)->create();

    app(DeleteWebauthnKeyAction::class)($this->webauthnKey);

    Event::assertDispatched(function (WebauthnKeyDeleted $event) {
        return $event->webauthnKey->is($this->webauthnKey);
    });
    Event::assertDispatchedTimes(WebauthnKeyDeleted::class, 1);
    Event::assertNotDispatched(TwoFactorAuthenticationWasDisabled::class);

    $this->assertDatabaseHas(WebauthnKey::class, [
        'id' => $otherKey->id,
    ]);

    expect($this->user->refresh())
        ->two_factor_enabled->toBeTrue();
});
