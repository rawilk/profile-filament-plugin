<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\AuthenticatorApps\DeleteTwoFactorAppAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppRemoved;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppUpdated;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasDisabled;
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
    login($this->user = User::factory()->withMfa()->create());

    $this->authenticator = AuthenticatorApp::factory()->for($this->user)->create();
});

it('can edit the name of the authenticator app', function () {
    livewire(AuthenticatorAppListItem::class, [
        'app' => $this->authenticator,
    ])
        ->mountAction('edit')
        ->assertActionDataSet([
            'name' => $this->authenticator->name,
        ])
        ->callAction('edit', data: [
            'name' => 'new name',
        ])
        ->assertHasNoActionErrors();

    expect($this->authenticator->refresh())
        ->name->toBe('new name');

    Event::assertDispatched(function (TwoFactorAppUpdated $event) {
        return $event->authenticatorApp->is($this->authenticator)
            && $event->user->is($this->user);
    });
});

it('requires a name to edit', function () {
    livewire(AuthenticatorAppListItem::class, ['app' => $this->authenticator])
        ->mountAction('edit')
        ->callAction('edit', data: [
            'name' => '',
        ])
        ->assertHasActionErrors([
            'name' => 'required',
        ]);

    Event::assertNotDispatched(TwoFactorAppUpdated::class);
});

it('requires app name to be unique', function () {
    AuthenticatorApp::factory()->for($this->user)->create(['name' => 'taken name']);

    livewire(AuthenticatorAppListItem::class, ['app' => $this->authenticator])
        ->mountAction('edit')
        ->callAction('edit', data: [
            'name' => 'taken name',
        ])
        ->assertHasActionErrors([
            'name' => 'unique',
        ]);

    Event::assertNotDispatched(TwoFactorAppUpdated::class);
});

test('edits to an authenticator app require authorization', function () {
    login(User::factory()->withMfa()->create());

    // Wrapped in a try-catch because I can't figure out a way to use
    // $this->authorize() in the `before` action callback and test
    // for it here...
    try {
        livewire(AuthenticatorAppListItem::class, ['app' => $this->authenticator])
            ->mountAction('edit')
            ->callAction('edit', data: [
                'name' => 'new name',
            ]);
    } catch (ErrorException) {
    }

    Event::assertNotDispatched(TwoFactorAppUpdated::class);

    expect($this->authenticator->refresh())
        ->name->not->toBe('new name');
});

it('can delete an authenticator app', function () {
    livewire(AuthenticatorAppListItem::class, ['app' => $this->authenticator])
        ->callAction('delete')
        ->assertNotified(__('profile-filament::pages/security.mfa.app.actions.delete.success_message', ['name' => e($this->authenticator->name)]))
        ->assertDispatched(MfaEvent::AppDeleted->value, appId: $this->authenticator->id)
        ->assertSet('app', null);

    $this->assertDatabaseMissing(AuthenticatorApp::class, [
        'id' => $this->authenticator->id,
    ]);

    Event::assertDispatched(TwoFactorAuthenticationWasDisabled::class);

    Event::assertDispatched(function (TwoFactorAppRemoved $event) {
        return $event->authenticatorApp->is($this->authenticator)
            && $event->user->is($this->user);
    });
});

it('requires sudo mode to delete an authenticator app', function () {
    enableSudoMode();

    livewire(AuthenticatorAppListItem::class, ['app' => $this->authenticator])
        ->call('mountAction', 'delete')
        ->assertActionMounted('sudoChallenge');
});

it('authorizes deletions against a policy', function () {
    login(User::factory()->withMfa()->create());

    try {
        livewire(AuthenticatorAppListItem::class, ['app' => $this->authenticator])
            ->callAction('delete');
    } catch (ErrorException) {
    }

    Event::assertNotDispatched(TwoFactorAppRemoved::class);

    $this->assertDatabaseHas(AuthenticatorApp::class, [
        'id' => $this->authenticator->id,
    ]);
});
