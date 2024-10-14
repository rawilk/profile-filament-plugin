<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\Webauthn\DeleteWebauthnKeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyDeleted;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUpdated;
use Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\WebauthnKey as Component;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    disableSudoMode();

    $this->record = WebauthnKey::factory()->for(User::factory()->withMfa())->notPasskey()->create([
        'attachment_type' => 'cross-platform',
    ]);

    login($this->record->user);

    config([
        'profile-filament.actions.delete_webauthn_key' => DeleteWebauthnKeyAction::class,
    ]);
});

it('renders', function () {
    livewire(Component::class, [
        'id' => $this->record->getKey(),
    ])
        ->assertSuccessful()
        ->assertSeeText($this->record->name);
});

it('can edit the name of the key', function () {
    livewire(Component::class, [
        'id' => $this->record->getKey(),
    ])
        ->mountAction('edit')
        ->assertActionDataSet([
            'name' => $this->record->name,
        ])
        ->callAction('edit', [
            'name' => 'new name',
        ])
        ->assertHasNoActionErrors();

    Event::assertDispatched(WebauthnKeyUpdated::class);

    expect($this->record->refresh())->name->toBe('new name');
});

it('requires a name when editing', function () {
    livewire(Component::class, [
        'id' => $this->record->getKey(),
    ])
        ->callAction('edit', [
            'name' => null,
        ])
        ->assertHasActionErrors([
            'name' => ['required'],
        ]);

    Event::assertNotDispatched(WebauthnKeyUpdated::class);
});

it('requires a unique name', function () {
    WebauthnKey::factory()->for($this->record->user)->create(['name' => 'taken name']);

    livewire(Component::class, [
        'id' => $this->record->getKey(),
    ])
        ->callAction('edit', [
            'name' => 'taken name',
        ])
        ->assertHasActionErrors([
            'name' => ['unique'],
        ]);

    expect($this->record->refresh())->name->not->toBe('taken name');
});

test('a user cannot edit another users security key', function () {
    $otherKey = WebauthnKey::factory()->for(User::factory())->create();

    livewire(Component::class, [
        'id' => $otherKey->getKey(),
    ])
        ->assertActionDisabled('edit');
});

it('can delete a webauthn key', function () {
    livewire(Component::class, [
        'id' => $this->record->getKey(),
    ])
        ->callAction('delete')
        ->assertDispatched(MfaEvent::WebauthnKeyDeleted->value, id: $this->record->getKey())
        ->assertSet('id', null);

    Event::assertDispatched(function (WebauthnKeyDeleted $event) {
        expect($event->webauthnKey)->toBe($this->record);

        return true;
    });

    $this->assertModelMissing($this->record);
});

it('can require sudo mode to delete a key', function () {
    enableSudoMode();

    livewire(Component::class, [
        'id' => $this->record->getKey(),
    ])
        ->call('mountAction', 'delete')
        ->assertSeeText(sudoChallengeTitle());
});

test('a user cannot delete another users key', function () {
    $otherKey = WebauthnKey::factory()->for(User::factory())->create();

    livewire(Component::class, [
        'id' => $otherKey->getKey(),
    ])
        ->assertSet('webauthnKey', null);
});

it('does not show the upgrade to passkey for ineligible keys', function () {
    livewire(Component::class, [
        'id' => $this->record->getKey(),
    ])
        ->assertActionHidden('upgrade');
});

it('can upgrade eligible keys to passkeys', function () {
    $record = WebauthnKey::factory()->for($this->record->user)->notPasskey()->create(['attachment_type' => 'platform']);

    livewire(Component::class, [
        'id' => $record->getKey(),
    ])
        ->assertActionVisible('upgrade');
});

it('does not show the upgrade to passkey action if passkeys are disabled in the current panel', function () {
    getPanelFeatures()->twoFactorAuthentication(
        passkeys: false,
    );

    $record = WebauthnKey::factory()->for($this->record->user)->notPasskey()->create(['attachment_type' => 'platform']);

    livewire(Component::class, [
        'id' => $record->getKey(),
    ])
        ->assertActionHidden('upgrade');
});
