<?php

declare(strict_types=1);

use Livewire\Component;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeChallenged;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Livewire\Sudo\SudoChallengeForm;
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

    config([
        'profile-filament.sudo.expires' => DateInterval::createFromDateString('10 minutes'),
    ]);
});

afterEach(function () {
    ProfileFilament::getPreferredMfaMethodUsing(null);
});

it('renders', function () {
    livewire(SudoChallengeForm::class)
        ->assertSuccessful()
        // Nothing is shown initially
        ->assertDontSeeText($this->user->email);
});

it('shows a sudo challenge', function () {
    livewire(SudoChallengeForm::class)
        ->dispatch('check-sudo', caller: 'foo', method: 'bar', data: ['foo' => 'bar'])
        ->assertSeeText($this->user->email)
        ->assertFormFieldIsVisible('password')
        ->assertSet('sudoCaller', 'foo')
        ->assertSet('sudoCallerMethod', 'bar')
        ->assertSet('sudoCallerData', ['foo' => 'bar'])
        ->assertActionMounted('sudoChallenge');

    Event::assertDispatched(SudoModeChallenged::class);
});

it('can show a users preferred sudo mode challenge', function () {
    ProfileFilament::getPreferredMfaMethodUsing(fn () => SudoChallengeMode::App->value);

    livewire(SudoChallengeForm::class)
        ->dispatch('check-sudo')
        ->assertSet('challengeMode', SudoChallengeMode::App);
});

it('confirms a users identity', function () {
    $caller = new class extends Component
    {
    };

    livewire(SudoChallengeForm::class)
        ->dispatch('check-sudo', caller: $caller::class, method: 'foo', data: ['foo' => 'bar'])
        ->fillForm([
            'password' => 'secret',
        ])
        ->call('confirm')
        ->assertDispatchedTo($caller::class, 'sudo-active', method: 'foo', data: ['foo' => 'bar'])
        ->assertActionNotMounted('sudoChallenge');
});

it('extends sudo mode if it is already active', function () {
    $this->freezeSecond();

    Sudo::activate();

    $caller = new class extends Component
    {
    };

    $this->travelTo(now()->addMinutes(10)->subSecond());

    livewire(SudoChallengeForm::class)
        ->dispatch('check-sudo', caller: $caller::class)
        ->assertDispatchedTo($caller::class, 'sudo-active')
        ->assertActionNotMounted('sudoChallenge')
        ->assertSet('sudoCaller', null);

    expect(now())->toBeSudoSessionValue();
});
