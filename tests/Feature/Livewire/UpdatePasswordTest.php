<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User as LaravelUser;
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

it('updates a users password', function () {
    livewire(UpdatePassword::class)
        ->assertFormFieldExists('current_password')
        ->assertFormFieldExists('password_confirmation')
        ->fillForm([
            'current_password' => 'secret',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->call('updatePassword')
        ->assertSuccessful()
        ->assertNotified()
        ->assertSet('data.current_password', null)
        ->assertSet('data.password', null)
        ->assertSet('data.password_confirmation', null);

    Event::assertDispatched(UserPasswordWasUpdated::class);

    expect('new-password')->toBePasswordFor($this->user->refresh());
});

it('requires your current password', function () {
    livewire(UpdatePassword::class)
        ->fillForm([
            'current_password' => null,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->call('updatePassword')
        ->assertHasFormErrors([
            'current_password' => ['required'],
        ]);
});

it('requires a correct current password', function () {
    livewire(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'incorrect',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->call('updatePassword')
        ->assertHasFormErrors([
            'current_password' => ['current_password'],
        ]);

    Event::assertNotDispatched(UserPasswordWasUpdated::class);
});

test('current password field can be omitted', function () {
    getPanelFeatures()->requireCurrentPasswordToUpdatePassword(false);

    livewire(UpdatePassword::class)
        ->assertFormFieldDoesNotExist('current_password')
        ->fillForm([
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->call('updatePassword')
        ->assertHasNoFormErrors();

    Event::assertDispatched(UserPasswordWasUpdated::class);
});

it('requires a new password', function () {
    livewire(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'secret',
            'new_password' => null,
            'password_confirmation' => 'new-password',
        ])
        ->call('updatePassword')
        ->assertHasFormErrors([
            'password' => ['required'],
        ]);
});

it('requires a password confirmation', function () {
    livewire(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'secret',
            'new_password' => 'new-password',
            'password_confirmation' => 'something-else',
        ])
        ->call('updatePassword')
        ->assertHasFormErrors([
            'password_confirmation' => ['same'],
        ]);
});

test('password confirmation can be omitted', function () {
    $this->mock(UpdatePasswordAction::class)
        ->shouldReceive('__invoke')
        ->with($this->user, 'new-password')
        ->once();

    getPanelFeatures()->requirePasswordConfirmationToUpdatePassword(false);

    livewire(UpdatePassword::class)
        ->assertFormFieldDoesNotExist('password_confirmation')
        ->fillForm([
            'current_password' => 'secret',
            'password' => 'new-password',
        ])
        ->call('updatePassword')
        ->assertHasNoFormErrors();
});

it('can hash the password for users that do not hash passwords automatically', function () {
    $model = new class extends LaravelUser
    {
        protected $table = 'users';

        protected $guarded = [];
    };

    $user = $model::create([
        'name' => fake()->name(),
        'email' => fake()->email(),
        'password' => Hash::make('secret'),
    ]);

    login($user);

    // Before we change the config value, the password shouldn't be hashed by our input.
    livewire(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'secret',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->call('updatePassword');

    expect($user->refresh())->password->toBe('new-password');

    // Reset the password now.
    $user->update(['password' => Hash::make('secret')]);

    config([
        'profile-filament.hash_user_passwords' => true,
    ]);

    // Now, with a new config value the password should be hashed for our user.
    livewire(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'secret',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->call('updatePassword');

    expect($user->refresh())
        ->password->not->toBe('new-password')
        ->and('new-password')->toBePasswordFor($user);
});
