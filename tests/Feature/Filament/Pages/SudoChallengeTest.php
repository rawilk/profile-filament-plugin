<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeActivated;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Filament\Pages\SudoChallenge;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    login($this->user = User::factory()->create(['password' => 'secret']));
});

it('renders', function () {
    livewire(SudoChallenge::class)
        ->assertSuccessful()
        ->assertSeeText($this->user->email);
});

it('redirects if sudo mode is already active', function () {
    Sudo::activate();

    livewire(SudoChallenge::class)
        ->assertRedirect(filament()->getUrl());
});

it('confirms a users identity', function () {
    $this->freezeSecond();

    livewire(SudoChallenge::class)
        ->fillForm([
            'password' => 'secret',
        ])
        ->call('confirm')
        ->assertHasNoFormErrors()
        ->assertRedirect(filament()->getUrl());

    expect(now())->toBeSudoSessionValue();

    Event::assertDispatched(SudoModeActivated::class);
});

it('requires a correct password to confirm identity', function () {
    livewire(SudoChallenge::class)
        ->call('setChallengeMode', SudoChallengeMode::Password->value)
        ->fillForm([
            'password' => 'invalid',
        ])
        ->call('confirm')
        ->assertSet('error', __('profile-filament::messages.sudo_challenge.password.invalid'))
        ->assertNoRedirect();

    Event::assertNotDispatched(SudoModeActivated::class);
});
