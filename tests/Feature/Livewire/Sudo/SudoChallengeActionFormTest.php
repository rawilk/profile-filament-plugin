<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Livewire\Sudo\SudoChallengeActionForm;
use Rawilk\ProfileFilament\ProfileFilament;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    $this->user = User::factory()
        ->withMfa()
        ->create([
            'id' => 1,
            'password' => 'secret',
        ]);

    login($this->user);
});

afterEach(function () {
    ProfileFilament::getPreferredMfaMethodUsing(null);
});

it('renders', function () {
    livewire(SudoChallengeActionForm::class)
        ->assertSuccessful()
        ->assertSeeText($this->user->email);
});

it('can complete a sudo challenge', function () {
    livewire(SudoChallengeActionForm::class, [
        'mode' => SudoChallengeMode::Password->value,
    ])
        ->call('setChallengeMode', SudoChallengeMode::Password->value)
        ->fillForm([
            'password' => 'secret',
        ])
        ->call('confirm')
        ->assertHasNoFormErrors()
        ->assertDispatched('sudo-active');
});

it('initially shows a users preferred sudo challenge mode', function () {
    ProfileFilament::getPreferredMfaMethodUsing(fn () => SudoChallengeMode::App->value);

    livewire(SudoChallengeActionForm::class)
        ->assertSet('challengeMode', SudoChallengeMode::App);
});
