<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Rawilk\ProfileFilament\Actions\DeleteAccountAction;
use Rawilk\ProfileFilament\Events\UserDeletedAccount;
use Rawilk\ProfileFilament\Livewire\DeleteAccount;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    actingAs($this->user = User::factory()->create(['email' => 'email@example.test']));

    config()->set('profile-filament.actions.delete_account', DeleteAccountAction::class);

    Filament::setCurrentPanel('admin');
    disableSudoMode();

    $this->component = DeleteAccount::class;
});

it('renders', function () {
    livewire($this->component)->assertSuccessful();
});

it('deletes the user account and logs them out', function () {
    livewire($this->component)
        ->callAction(
            TestAction::make('deleteAccount')->schemaComponent(schema: 'infolist'),
            data: [
                'email' => 'email@example.test',
            ]
        )
        ->assertHasNoActionErrors()
        ->assertRedirect(Filament::getLoginUrl());

    assertGuest();

    assertModelMissing($this->user);

    Event::assertDispatched(function (UserDeletedAccount $event) {
        expect($event->user)->toBe($this->user);

        return true;
    });
});

describe('validation', function () {
    test('email is required', function () {
        livewire($this->component)
            ->callAction(
                TestAction::make('deleteAccount')->schemaComponent(schema: 'infolist'),
                data: [
                    'email' => null,
                ]
            )
            ->assertHasActionErrors([
                'email' => 'required',
            ])
            ->assertNoRedirect();

        assertAuthenticatedAs($this->user);

        assertModelExists($this->user);
    });

    test('email must be the auth user email', function () {
        livewire($this->component)
            ->callAction(
                TestAction::make('deleteAccount')->schemaComponent(schema: 'infolist'),
                data: [
                    'email' => 'incorrect@example.test',
                ]
            )
            ->assertHasActionErrors([
                'email' => __('profile-filament::pages/settings.delete_account.actions.delete.incorrect_email'),
            ])
            ->assertNoRedirect();

        assertAuthenticatedAs($this->user);

        assertModelExists($this->user);
    });
});

it('can require sudo mode', function () {
    enableSudoMode();

    livewire($this->component)
        ->mountAction(TestAction::make('deleteAccount')->schemaComponent(schema: 'infolist'))
        ->assertActionMounted('sudoChallenge')
        ->assertActionNotMounted(TestAction::make('deleteAccount')->schemaComponent(schema: 'infolist'));

    assertModelExists($this->user);

    assertAuthenticatedAs($this->user);
});
