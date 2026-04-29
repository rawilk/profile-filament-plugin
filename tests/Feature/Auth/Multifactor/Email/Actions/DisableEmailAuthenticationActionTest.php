<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorDisabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Actions\DisableEmailAuthenticationAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Events\EmailAuthenticationWasDisabled;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

beforeEach(function () {
    Event::fake();

    config()->set('profile-filament.actions.mark_multifactor_disabled', MarkMultiFactorDisabledAction::class);

    $this->action = DisableEmailAuthenticationAction::class;
});

it('disables email authentication for a user', function () {
    $user = User::factory()->withEmailAuthentication()->create();

    expect($user->hasEmailAuthentication())->toBeTrue();

    app($this->action)($user);

    expect($user->fresh()->hasEmailAuthentication())->toBeFalse();
});

it('fires a EmailAuthenticationWasDisabled event', function () {
    $user = User::factory()->withEmailAuthentication()->create();

    app($this->action)($user);

    Event::assertDispatched(EmailAuthenticationWasDisabled::class, function (EmailAuthenticationWasDisabled $event) use ($user) {
        expect($event->user)->toBe($user);

        return true;
    });
});

it('calls the MarkMultiFactorDisabled action', function () {
    $user = User::factory()->withEmailAuthentication()->create();

    $this->mock(MarkMultiFactorDisabledAction::class)
        ->shouldReceive('__invoke')
        ->with($user)
        ->once();

    app($this->action)($user);
});

it('does nothing if user does not have email authentication enabled', function () {
    $user = User::factory()->create();

    expect($user->hasEmailAuthentication())->toBeFalse();

    $this->mock(MarkMultiFactorDisabledAction::class)
        ->shouldNotReceive('__invoke');

    app($this->action)($user);

    Event::assertNotDispatched(EmailAuthenticationWasDisabled::class);
});

it('requires user to be an instance of HasEmailAuthentication', function () {
    $user = new class extends Illuminate\Foundation\Auth\User
    {
    };

    app($this->action)($user);
})->throws(
    LogicException::class,
    HasEmailAuthentication::class,
);
