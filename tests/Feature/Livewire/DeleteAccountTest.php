<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Rawilk\ProfileFilament\Actions\DeleteAccountAction;
use Rawilk\ProfileFilament\Events\UserDeletedAccount;
use Rawilk\ProfileFilament\Livewire\DeleteAccount;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    login($this->user = User::factory()->create(['email' => 'email@example.com']));

    config([
        'profile-filament.actions.delete_account' => DeleteAccountAction::class,
    ]);

    disableSudoMode();
});

it('deletes a user account and logs the user out', function () {
    livewire(DeleteAccount::class)
        ->mountAction('delete')
        ->setActionData(['email' => 'email@example.com'])
        ->callAction('delete')
        ->assertSessionHas('success', __('profile-filament::pages/settings.delete_account.actions.delete.success'))
        ->assertRedirect('/admin/login');

    Event::assertDispatched(function (UserDeletedAccount $event) {
        expect($event->user)->toBe($this->user);

        return true;
    });

    $this->assertDatabaseMissing('users', [
        'id' => $this->user->id,
    ]);

    expect(auth()->check())->toBeFalse();
});

it('requires an email to process action', function () {
    livewire(DeleteAccount::class)
        ->mountAction('delete')
        ->setActionData(['email' => ''])
        ->callAction('delete')
        ->assertHasActionErrors([
            'email' => 'required',
        ])
        ->assertNoRedirect();

    expect(auth()->check())->toBeTrue();
});

it('requires the correct email to process action', function () {
    livewire(DeleteAccount::class)
        ->mountAction('delete')
        ->setActionData(['email' => 'invalid@example.com'])
        ->callAction('delete')
        ->assertHasActionErrors([
            'email',
        ])
        ->assertNoRedirect();

    expect(auth()->check())->toBeTrue();
});

it('requires sudo mode', function () {
    enableSudoMode();

    livewire(DeleteAccount::class)
        ->call('mountAction', 'delete')
        ->assertActionMounted('sudoChallenge');
});
