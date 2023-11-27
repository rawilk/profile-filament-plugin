<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User as LaravelUser;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Rawilk\ProfileFilament\Actions\UpdatePasswordAction;
use Rawilk\ProfileFilament\Events\UserPasswordWasUpdated;
use Rawilk\ProfileFilament\Livewire\UpdatePassword;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    config([
        'profile-filament.actions.update_password' => UpdatePasswordAction::class,
        'profile-filament.hash_user_passwords' => false,
    ]);

    login($this->user = User::factory()->create(['password' => 'secret']));
});

it("updates a user's password", function () {
    livewire(UpdatePassword::class)
        ->set('data.current_password', 'secret')
        ->set('data.password', 'new-password')
        ->set('data.password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertSuccessful()
        ->assertNotified()
        ->assertSet('data.current_password', null)
        ->assertSet('data.password', null)
        ->assertSet('data.password_confirmation', null);

    Event::assertDispatched(UserPasswordWasUpdated::class);

    expect(Hash::check('new-password', $this->user->refresh()->getAuthPassword()))->toBeTrue();
});

it('can hash a password automatically', function () {
    $user = NeedsHashUser::create([
        'name' => 'foo',
        'email' => 'needs-hash@example.test',
        'password' => Hash::make('secret'),
    ]);

    login($user);

    // Before we change the config value, the password shouldn't be hashed by our input.
    livewire(UpdatePassword::class)
        ->set('data.current_password', 'secret')
        ->set('data.password', 'new-password')
        ->set('data.password_confirmation', 'new-password')
        ->call('updatePassword');

    $user->refresh();

    expect($user->password)->toBe('new-password');

    $user->update(['password' => Hash::make('secret')]);

    config([
        'profile-filament.hash_user_passwords' => true,
    ]);

    livewire(UpdatePassword::class)
        ->set('data.current_password', 'secret')
        ->set('data.password', 'new-password')
        ->set('data.password_confirmation', 'new-password')
        ->call('updatePassword');

    $user->refresh();

    expect($user->password)->not->toBe('new-password')
        ->and(Hash::check('new-password', $user->password))->toBeTrue();
});

it('requires your current password', function () {
    livewire(UpdatePassword::class)
        ->set('data.current_password', '')
        ->set('data.password', 'new-password')
        ->set('data.password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasFormErrors([
            'current_password' => 'required',
        ]);
});

it('requires a correct current password', function () {
    livewire(UpdatePassword::class)
        ->assertSee('id="data.current_password"', false)
        ->set('data.current_password', 'incorrect')
        ->set('data.password', 'new-password')
        ->set('data.password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasFormErrors([
            'current_password' => 'current-password',
        ]);

    Event::assertNotDispatched(UserPasswordWasUpdated::class);
});

test('current password field can be omitted', function () {
    getPanelFeatures()->requireCurrentPasswordToUpdatePassword(false);

    livewire(UpdatePassword::class)
        ->assertDontSee('id="data.current_password"', false)
        ->set('data.password', 'new-password')
        ->set('data.password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoFormErrors();

    Event::assertDispatched(UserPasswordWasUpdated::class);
});

it('requires a new password', function () {
    livewire(UpdatePassword::class)
        ->set('data.current_password', 'secret')
        ->set('data.password', '')
        ->set('data.password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasFormErrors([
            'password' => 'required',
        ]);
});

it('requires a password confirmation', function () {
    livewire(UpdatePassword::class)
        ->set('data.current_password', 'secret')
        ->set('data.password', 'new-password')
        ->set('data.password_confirmation', '')
        ->call('updatePassword')
        ->assertHasFormErrors([
            'password_confirmation' => 'required',
        ]);
});

it('requires password confirmation to match', function () {
    livewire(UpdatePassword::class)
        ->set('data.current_password', 'secret')
        ->set('data.password', 'new-password')
        ->set('data.password_confirmation', 'no-match')
        ->call('updatePassword')
        ->assertHasFormErrors([
            'password_confirmation' => 'same',
        ]);

    Event::assertNotDispatched(UserPasswordWasUpdated::class);
});

test('password confirmation can be omitted', function () {
    getPanelFeatures()->requirePasswordConfirmationToUpdatePassword(false);

    livewire(UpdatePassword::class)
        ->assertDontSee('id="password_confirmation"', false)
        ->set('data.current_password', 'secret')
        ->set('data.password', 'new-password')
        ->call('updatePassword')
        ->assertHasNoFormErrors();

    Event::assertDispatched(UserPasswordWasUpdated::class);
});

class NeedsHashUser extends LaravelUser
{
    protected $table = 'users';

    protected $guarded = [];
}
