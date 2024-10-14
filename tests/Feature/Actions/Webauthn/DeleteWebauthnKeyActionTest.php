<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Actions\Webauthn\DeleteWebauthnKeyAction;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyDeleted;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    config([
        'profile-filament.actions.mark_two_factor_disabled' => MarkTwoFactorEnabledAction::class,
    ]);

    Event::fake();

    $this->record = WebauthnKey::factory()
        ->for(User::factory())
        ->create();
});

it('deletes a webauthn key', function () {
    app(DeleteWebauthnKeyAction::class)($this->record);

    Event::assertDispatched(function (WebauthnKeyDeleted $event) {
        expect($event->user)->toBe($this->record->user)
            ->and($event->webauthnKey)->toBe($this->record);

        return true;
    });

    $this->assertModelMissing($this->record);
});

it('calls the action to disable mfa for a user', function () {
    $this->mock(MarkTwoFactorEnabledAction::class)
        ->shouldReceive('__invoke')
        ->with($this->record->user)
        ->once();

    app(DeleteWebauthnKeyAction::class)($this->record);
});
