<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\Webauthn\DeleteWebauthnKeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyDeleted;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUpdated;
use Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\WebauthnKey as WebauthnKeyComponent;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();
    login($this->user = User::factory()->withMfa()->create());

    disableSudoMode();

    $this->webauthnKey = WebauthnKey::factory()->for($this->user)->notPasskey()->create(['attachment_type' => 'cross-platform']);

    config([
        'profile-filament.actions.delete_webauthn_key' => DeleteWebauthnKeyAction::class,
    ]);
});

it('can edit the name of a webauthn key', function () {
    livewire(WebauthnKeyComponent::class, ['webauthnKey' => $this->webauthnKey])
        ->assertSuccessful()
        ->mountAction('edit')
        ->assertActionDataSet([
            'name' => $this->webauthnKey->name,
        ])
        ->callAction('edit', [
            'name' => 'new name',
        ])
        ->assertSuccessful()
        ->assertNotified();

    Event::assertDispatched(WebauthnKeyUpdated::class);

    expect($this->webauthnKey->refresh())
        ->name->toBe('new name');
});

it('requires a name', function () {
    livewire(WebauthnKeyComponent::class, ['webauthnKey' => $this->webauthnKey])
        ->callAction('edit', [
            'name' => '',
        ])
        ->assertHasActionErrors([
            'name' => 'required',
        ])
        ->assertNotNotified();

    Event::assertNotDispatched(WebauthnKeyUpdated::class);

    expect($this->webauthnKey->refresh())
        ->name->not->toBe('')
        ->name->not->toBeNull();
});

it('requires a unique name', function () {
    WebauthnKey::factory()->for($this->user)->create(['name' => 'taken name']);

    livewire(WebauthnKeyComponent::class, ['webauthnKey' => $this->webauthnKey])
        ->callAction('edit', [
            'name' => 'taken name',
        ])
        ->assertHasActionErrors([
            'name' => 'unique',
        ]);

    Event::assertNotDispatched(WebauthnKeyUpdated::class);

    expect($this->webauthnKey->refresh())
        ->name->not->toBe('taken name');
});

it('requires authorization to edit a webauthn key', function () {
    login(User::factory()->withMfa()->create());

    try {
        livewire(WebauthnKeyComponent::class, ['webauthnKey' => $this->webauthnKey])
            ->callAction('edit', [
                'name' => 'new name',
            ]);
    } catch (ErrorException) {
    }

    Event::assertNotDispatched(WebauthnKeyUpdated::class);

    expect($this->webauthnKey->refresh())
        ->name->not->toBe('new name');
});

it('can delete a webauthn key', function () {
    livewire(WebauthnKeyComponent::class, ['webauthnKey' => $this->webauthnKey])
        ->callAction('delete')
        ->assertNotified(__('profile-filament::pages/security.mfa.webauthn.actions.delete.success_message', ['name' => e($this->webauthnKey->name)]))
        ->assertDispatched(MfaEvent::WebauthnKeyDeleted->value, id: $this->webauthnKey->id)
        ->assertSet('webauthnKey', null);

    Event::assertDispatched(function (WebauthnKeyDeleted $event) {
        return $event->webauthnKey->is($this->webauthnKey);
    });

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'id' => $this->webauthnKey->id,
    ]);
});

it('requires sudo mode to delete a webauthn key', function () {
    enableSudoMode();

    livewire(WebauthnKeyComponent::class, ['webauthnKey' => $this->webauthnKey])
        ->call('mountAction', 'delete')
        ->assertActionMounted('sudoChallenge');
});

it('requires authorization to delete a webauthn key', function () {
    login(User::factory()->withMfa()->create());

    try {
        livewire(WebauthnKeyComponent::class, ['webauthnKey' => $this->webauthnKey])
            ->callAction('delete');
    } catch (ErrorException) {
    }

    Event::assertNotDispatched(WebauthnKeyDeleted::class);

    $this->assertDatabaseHas(WebauthnKey::class, [
        'id' => $this->webauthnKey->id,
    ]);
});

it('does not show the upgrade to passkey action for ineligible keys', function () {
    livewire(WebauthnKeyComponent::class, ['webauthnKey' => $this->webauthnKey])
        ->assertActionHidden('upgrade');
});

it('can upgrade an eligible key to a passkey', function () {
    $webauthnKey = WebauthnKey::factory()->for($this->user)->notPasskey()->create(['attachment_type' => 'platform']);

    livewire(WebauthnKeyComponent::class, ['webauthnKey' => $webauthnKey])
        ->assertActionVisible('upgrade');
});

it('does not show the passkey upgrade button if passkeys are disabled', function () {
    getPanelFeatures()->twoFactorAuthentication(
        passkeys: false,
    );

    $webauthnKey = WebauthnKey::factory()->for($this->user)->notPasskey()->create(['attachment_type' => 'platform']);

    livewire(WebauthnKeyComponent::class, ['webauthnKey' => $webauthnKey])
        ->assertActionHidden('upgrade');
});
