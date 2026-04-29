<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorDisabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\DeleteSecurityKeyAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Events\SecurityKeyWasDeleted;
use Rawilk\ProfileFilament\Models\WebauthnKey;

use function Pest\Laravel\assertModelMissing;

beforeEach(function () {
    Event::fake();

    config()->set('profile-filament.actions.mark_multifactor_disabled', MarkMultiFactorDisabledAction::class);

    $this->action = DeleteSecurityKeyAction::class;

    $this->securityKey = WebauthnKey::factory()->create();
});

it('deletes a security key', function () {
    app($this->action)($this->securityKey);

    assertModelMissing($this->securityKey);
});

it('calls the MarkMultiFactorDisabledAction', function () {
    $this->mock(MarkMultiFactorDisabledAction::class)
        ->shouldReceive('__invoke')
        ->with($this->securityKey->user)
        ->once();

    app($this->action)($this->securityKey);
});

it('fires a SecurityKeyWasDeleted event', function () {
    app($this->action)($this->securityKey);

    Event::assertDispatched(SecurityKeyWasDeleted::class, function (SecurityKeyWasDeleted $event) {
        expect($event->webauthnKey)->toBe($this->securityKey);

        return true;
    });
});
