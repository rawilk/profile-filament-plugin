<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorEnabledAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Actions\EnableEmailAuthenticationAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Events\EmailAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

beforeEach(function () {
    Event::fake();

    config()->set('profile-filament.actions.mark_multifactor_enabled', MarkMultiFactorEnabledAction::class);

    $this->action = EnableEmailAuthenticationAction::class;
});

it('enables email authentication for a user', function () {
    /** @var HasEmailAuthentication $user */
    $user = User::factory()->create();

    expect($user->hasEmailAuthentication())->toBeFalse();

    app($this->action)($user);

    expect($user->hasEmailAuthentication())->toBeTrue();
});

it('fires a EmailAuthenticationWasEnabled event', function () {
    /** @var HasEmailAuthentication $user */
    $user = User::factory()->create();

    app($this->action)($user);

    Event::assertDispatched(EmailAuthenticationWasEnabled::class, function (EmailAuthenticationWasEnabled $event) use ($user) {
        expect($event->user)->toBe($user);

        return true;
    });
});

it('calls the MarkMultiFactorEnabled action', function () {
    /** @var HasEmailAuthentication $user */
    $user = User::factory()->create();

    $this->mock(MarkMultiFactorEnabledAction::class)
        ->shouldReceive('__invoke')
        ->with($user)
        ->once();

    app($this->action)($user);
});

it('does nothing if user already has email authentication enabled', function () {
    $user = User::factory()->withEmailAuthentication()->create();

    $this->mock(MarkMultiFactorEnabledAction::class)
        ->shouldNotReceive('__invoke');

    app($this->action)($user);

    Event::assertNotDispatched(EmailAuthenticationWasEnabled::class);
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
