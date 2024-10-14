<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\AuthenticatorApps\DeleteTwoFactorAppAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppRemoved;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppUpdated;
use Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\AuthenticatorAppListItem;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    config([
        'profile-filament.actions.delete_authenticator_app' => DeleteTwoFactorAppAction::class,
    ]);

    disableSudoMode();

    Event::fake();

    $this->record = AuthenticatorApp::factory()
        ->for(User::factory()->withMfa())
        ->create(['name' => 'my app']);

    login($this->record->user);
});

it('renders', function () {
    livewire(AuthenticatorAppListItem::class, [
        'app' => $this->record,
    ])
        ->assertSuccessful()
        ->assertSeeText($this->record->name);
});

it('can edit the name', function () {
    livewire(AuthenticatorAppListItem::class, [
        'app' => $this->record,
    ])
        ->callAction('edit', [
            'name' => 'new name',
        ])
        ->assertHasNoActionErrors();

    expect($this->record->refresh())->name->toBe('new name');

    Event::assertDispatched(function (TwoFactorAppUpdated $event) {
        expect($event->authenticatorApp)->toBe($this->record)
            ->and($event->user)->toBe($this->record->user);

        return true;
    });
});

it('requires a name to edit', function () {
    livewire(AuthenticatorAppListItem::class, [
        'app' => $this->record,
    ])
        ->callAction('edit', [
            'name' => null,
        ])
        ->assertHasActionErrors([
            'name' => ['required'],
        ]);

    Event::assertNotDispatched(TwoFactorAppUpdated::class);
});

it('requires a unique app name', function () {
    AuthenticatorApp::factory()->for($this->record->user)->create(['name' => 'taken name']);

    livewire(AuthenticatorAppListItem::class, [
        'app' => $this->record,
    ])
        ->callAction('edit', [
            'name' => 'taken name',
        ])
        ->assertHasActionErrors([
            'name' => ['unique'],
        ]);
});

test('a user cannot edit another users app', function () {
    $otherApp = AuthenticatorApp::factory()->for(User::factory()->withMfa())->create();

    livewire(AuthenticatorAppListItem::class, [
        'app' => $otherApp,
    ])
        ->assertActionHidden('edit');
});

it('can delete an app', function () {
    livewire(AuthenticatorAppListItem::class, [
        'app' => $this->record,
    ])
        ->assertActionVisible('delete')
        ->callAction('delete')
        ->assertHasNoActionErrors()
        ->assertDispatched(MfaEvent::AppDeleted->value, appId: $this->record->getKey())
        ->assertSet('app', null);

    $this->assertModelMissing($this->record);

    Event::assertDispatched(function (TwoFactorAppRemoved $event) {
        expect($event->authenticatorApp)->toBe($this->record)
            ->and($event->user)->toBe($this->record->user);

        return true;
    });
});

it('can require sudo mode to delete an app', function () {
    enableSudoMode();

    $this->mock(DeleteTwoFactorAppAction::class)
        ->shouldNotReceive('__invoke');

    livewire(AuthenticatorAppListItem::class, [
        'app' => $this->record,
    ])
        ->call('mountAction', 'delete')
        ->assertSeeText(sudoChallengeTitle());
});

test('a user cannot delete another users app', function () {
    $otherApp = AuthenticatorApp::factory()->for(User::factory()->withMfa())->create();

    livewire(AuthenticatorAppListItem::class, [
        'app' => $otherApp,
    ])
        ->assertActionHidden('delete');
});
