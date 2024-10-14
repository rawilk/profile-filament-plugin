<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\AuthenticatorApps\DeleteTwoFactorAppAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorDisabledAction;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppRemoved;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    config([
        'profile-filament.actions.mark_two_factor_disabled' => MarkTwoFactorDisabledAction::class,
    ]);

    Event::fake();

    $this->record = AuthenticatorApp::factory()
        ->for(User::factory())
        ->create();
});

it('deletes an authenticator app for a user', function () {
    app(DeleteTwoFactorAppAction::class)($this->record);

    Event::assertDispatched(function (TwoFactorAppRemoved $event) {
        expect($event->user->is($this->record->user))
            ->and($event->authenticatorApp->is($this->record));

        return true;
    });

    $this->assertModelMissing($this->record);
});

it('calls the action to disable mfa for a user', function () {
    $this->mock(MarkTwoFactorDisabledAction::class)
        ->shouldReceive('__invoke')
        ->with($this->record->user)
        ->once();

    app(DeleteTwoFactorAppAction::class)($this->record);
});
