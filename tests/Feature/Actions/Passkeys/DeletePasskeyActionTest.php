<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\Passkeys\DeletePasskeyAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorDisabledAction;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyDeleted;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasDisabled;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    Event::fake();

    config([
        'profile-filament.actions.mark_two_factor_disabled' => MarkTwoFactorDisabledAction::class,
    ]);

    $this->user = User::factory()->withMfa()->create();
    $this->passkey = WebauthnKey::factory()->passkey()->for($this->user)->create();
});

it('deletes a passkey', function () {
    Cache::shouldReceive('forget')->with('user:1:has-passkeys');

    app(DeletePasskeyAction::class)($this->passkey);

    Event::assertDispatched(function (PasskeyDeleted $event) {
        expect($event->passkey)->toBe($this->passkey);

        return true;
    });

    Event::assertDispatched(TwoFactorAuthenticationWasDisabled::class);

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'id' => $this->passkey->id,
    ]);

    expect($this->user->refresh())->two_factor_enabled->toBeFalse();
});

it('does not disable 2fa for a user if they have other mfa methods available', function () {
    WebauthnKey::factory()->notPasskey()->for($this->user)->create();

    app(DeletePasskeyAction::class)($this->passkey);

    Event::assertDispatched(PasskeyDeleted::class);
    Event::assertNotDispatched(TwoFactorAuthenticationWasDisabled::class);

    expect($this->user->refresh())->two_factor_enabled->toBeTrue();
});
