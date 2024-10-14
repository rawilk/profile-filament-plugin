<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\Passkeys\DeletePasskeyAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorDisabledAction;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyDeleted;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    Event::fake();

    config([
        'profile-filament.actions.mark_two_factor_disabled' => MarkTwoFactorDisabledAction::class,
    ]);

    $this->passkey = WebauthnKey::factory()
        ->passkey()
        ->for(User::factory()->create(['id' => 1]))
        ->create();
});

it('deletes a passkey', function () {
    Cache::shouldReceive('forget')->with('user:1:has-passkeys')->once();

    app(DeletePasskeyAction::class)($this->passkey);

    Event::assertDispatched(function (PasskeyDeleted $event) {
        expect($event->passkey)->toBe($this->passkey);

        return true;
    });

    $this->assertModelMissing($this->passkey);
});

it('calls the action to disable mfa for a user', function () {
    $this->mock(MarkTwoFactorDisabledAction::class)
        ->shouldReceive('__invoke')
        ->with($this->passkey->user)
        ->once();

    app(DeletePasskeyAction::class)($this->passkey);
});
