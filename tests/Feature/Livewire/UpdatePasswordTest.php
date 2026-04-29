<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\UpdatePasswordAction;
use Rawilk\ProfileFilament\Events\UserPasswordWasUpdated;
use Rawilk\ProfileFilament\Livewire\UpdatePassword;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\be;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    config()->set('profile-filament.hash_user_passwords', false);
    config()->set('profile-filament.actions.update_password', UpdatePasswordAction::class);

    actingAs($this->user = User::factory()->create(['password' => 'secret']));

    $this->component = UpdatePassword::class;
});

it('renders', function () {
    livewire($this->component)->assertSuccessful();
});

it('updates the password for a user', function () {
    livewire($this->component)
        ->assertFormFieldExists('current_password')
        ->assertFormFieldExists('password_confirmation')
        ->fillForm([
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'current_password' => 'secret',
        ])
        ->call('updatePassword')
        ->assertSuccessful()
        ->assertNotified()
        ->assertSet('data.password', null)
        ->assertSet('data.password_confirmation', null)
        ->assertSet('data.current_password', null);

    Event::assertDispatched(UserPasswordWasUpdated::class);

    expect('new-password')->toBePasswordFor($this->user->refresh());
});

describe('validation', function () {
    test('requires current  password', function () {
        livewire($this->component)
            ->fillForm([
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'current_password' => '',
            ])
            ->call('updatePassword')
            ->assertHasFormErrors([
                'current_password' => 'required',
            ]);
    });

    test('requires correct current password', function () {
        livewire($this->component)
            ->fillForm([
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'current_password' => 'invalid',
            ])
            ->call('updatePassword')
            ->assertHasFormErrors([
                'current_password' => 'current_password',
            ]);

        Event::assertNotDispatched(UserPasswordWasUpdated::class);
    });

    test('new password is required', function () {
        livewire($this->component)
            ->fillForm([
                'password' => '',
                'password_confirmation' => 'new-password',
                'current_password' => 'secret',
            ])
            ->call('updatePassword')
            ->assertHasFormErrors([
                'password' => 'required',
            ]);
    });

    test('password must be confirmed', function () {
        livewire($this->component)
            ->fillForm([
                'password' => 'new-password',
                'password_confirmation' => 'does-not-match',
                'current_password' => 'secret',
            ])
            ->call('updatePassword')
            ->assertHasFormErrors([
                'password_confirmation' => 'same',
            ]);
    });
});

describe('options', function () {
    test('current password field can be omitted', function () {
        getPlugin()->requireCurrentPassword(false);

        livewire($this->component)
            ->assertFormFieldDoesNotExist('current_password')
            ->fillForm([
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->call('updatePassword')
            ->assertHasNoFormErrors();

        Event::assertDispatched(UserPasswordWasUpdated::class);
    });

    test('password confirmation field can be omitted', function () {
        getPlugin()->requirePasswordConfirmation(false);

        livewire($this->component)
            ->assertFormFieldDoesNotExist('current_password')
            ->fillForm([
                'password' => 'new-password',
                'current_password' => 'secret',
            ])
            ->call('updatePassword')
            ->assertHasNoFormErrors();

        Event::assertDispatched(UserPasswordWasUpdated::class);
    });

    it('can hash the password for user models that do not hash the password automatically', function () {
        $model = new class extends Illuminate\Foundation\Auth\User
        {
            protected $table = 'users';

            protected $guarded = [];
        };

        $user = $model::create([
            'name' => fake()->name(),
            'email' => fake()->email(),
            'password' => Hash::make('secret'),
        ]);

        be($user);

        // Before we change the config value, the form shouldn't be hashing the value.
        livewire($this->component)
            ->fillForm([
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'current_password' => 'secret',
            ])
            ->call('updatePassword');

        expect($user->refresh())->password->toBe('new-password'); // not hashed

        $user->update(['password' => Hash::make('secret')]);

        config()->set('profile-filament.hash_user_passwords', true);

        // Now the form should hash the password for us.
        livewire($this->component)
            ->fillForm([
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'current_password' => 'secret',
            ])
            ->call('updatePassword');

        expect($user->refresh())
            ->password->not->toBe('new-password')
            ->and('new-password')->toBePasswordFor($user);
    });
});
