<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorDisabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Actions\DeleteAuthenticatorAppAction;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Events\AuthenticatorAppWasDeleted;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

use function Pest\Laravel\assertModelMissing;

beforeEach(function () {
    config()->set('profile-filament.actions.mark_multifactor_disabled', MarkMultiFactorDisabledAction::class);

    Event::fake();

    $this->action = DeleteAuthenticatorAppAction::class;

    $this->record = AuthenticatorApp::factory()->create();
});

it('deletes an authenticator app', function () {
    app($this->action)($this->record);

    assertModelMissing($this->record);
});

it('calls the MarkMultiFactorDisabledAction', function () {
    $this->mock(MarkMultiFactorDisabledAction::class)
        ->shouldReceive('__invoke')
        ->with($this->record->user)
        ->once();

    app($this->action)($this->record);
});

it('fires a AuthenticatorAppWasDeleted event', function () {
    app($this->action)($this->record);

    Event::assertDispatched(AuthenticatorAppWasDeleted::class, function (AuthenticatorAppWasDeleted $event) {
        expect($event->authenticatorApp)->toBe($this->record);

        return true;
    });
});
