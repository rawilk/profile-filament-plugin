<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorDisabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Actions\DeleteAuthenticatorAppAction;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Events\AuthenticatorAppWasDeleted;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    config([
        'profile-filament.actions.mark_two_factor_disabled' => MarkMultiFactorDisabledAction::class,
    ]);

    Event::fake();

    $this->record = AuthenticatorApp::factory()
        ->for(User::factory())
        ->create();
});

it('deletes an authenticator app for a user', function () {
    app(DeleteAuthenticatorAppAction::class)($this->record);

    Event::assertDispatched(function (AuthenticatorAppWasDeleted $event) {
        expect($event->user->is($this->record->user))
            ->and($event->authenticatorApp->is($this->record));

        return true;
    });

    $this->assertModelMissing($this->record);
});

it('calls the action to disable mfa for a user', function () {
    $this->mock(MarkMultiFactorDisabledAction::class)
        ->shouldReceive('__invoke')
        ->with($this->record->user)
        ->once();

    app(DeleteAuthenticatorAppAction::class)($this->record);
});
