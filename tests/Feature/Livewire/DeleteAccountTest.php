<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\DeleteAccountAction;
use Rawilk\ProfileFilament\Events\UserDeletedAccount;
use Rawilk\ProfileFilament\Livewire\DeleteAccount;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    login($this->user = User::factory()->create(['email' => 'email@example.test']));

    config([
        'profile-filament.actions.delete_account' => DeleteAccountAction::class,
    ]);

    disableSudoMode();
});

it('deletes a users account and logs the user out', function () {
    livewire(DeleteAccount::class)
        ->callInfolistAction('.deleteAccountAction', 'deleteAccount', data: [
            'email' => 'email@example.test',
        ])
        ->assertHasNoInfolistActionErrors()
        ->assertRedirect('/admin/login');

    $this->assertGuest();

    $this->assertModelMissing($this->user);

    Event::assertDispatched(function (UserDeletedAccount $event) {
        expect($event->user)->toBe($this->user);

        return true;
    });
});

it('requires an email to process the action', function () {
    livewire(DeleteAccount::class)
        ->callInfolistAction('.deleteAccountAction', 'deleteAccount', data: [
            'email' => null,
        ])
        ->assertHasInfolistActionErrors([
            'email' => ['required'],
        ])
        ->assertNoRedirect();

    $this->assertAuthenticated();
});

it('requires the correct email to process the action', function () {
    livewire(DeleteAccount::class)
        ->callInfolistAction('.deleteAccountAction', 'deleteAccount', data: [
            'email' => 'incorrect@example.test',
        ])
        ->assertHasInfolistActionErrors([
            'email' => [__('profile-filament::pages/settings.delete_account.actions.delete.incorrect_email')],
        ])
        ->assertNoRedirect();

    $this->assertAuthenticated();

    $this->assertModelExists($this->user);
});

it('can require sudo mode', function () {
    enableSudoMode();

    livewire(DeleteAccount::class)
        ->call('mountInfolistAction', 'deleteAccount', '.deleteAccountAction', 'infolist')
        ->assertInfolistActionNotMounted('.deleteAccountAction', 'deleteAccount')
        ->assertSeeText(sudoChallengeTitle());
});
