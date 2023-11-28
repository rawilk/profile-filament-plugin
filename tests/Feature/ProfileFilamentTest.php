<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Actions\Auth\PrepareUserSession;
use Rawilk\ProfileFilament\Enums\Livewire\MfaChallengeMode;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\ProfileFilament;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

beforeEach(function () {
    $this->service = new ProfileFilament;
});

afterEach(function () {
    ProfileFilament::$findUserTimezoneUsingCallback = null;
    ProfileFilament::$shouldCheckForMfaCallback = null;
    ProfileFilament::$getPreferredMfaMethodCallback = null;
    ProfileFilament::$mfaAuthenticationPipelineCallback = null;
});

it('can get the timezone for a user', function () {
    $user = User::factory()->make();
    $user->timezone = 'America/Chicago';

    expect($this->service->userTimezone($user))->toBe('America/Chicago');

    $user->timezone = null;

    expect($this->service->userTimezone($user))->toBe('UTC');
});

test('a callback can be used for getting a user timezone', function () {
    ProfileFilament::findUserTimezoneUsing(fn ($user): string => $user->email);

    $user = User::factory()->make(['email' => 'email@example.test']);

    expect($this->service->userTimezone($user))->toBe('email@example.test');
});

it('can determine if mfa should be enforced', function () {
    $request = new Request;
    $user = User::factory()->make();

    expect($this->service->shouldCheckForMfa($request, $user))->toBeTrue();

    ProfileFilament::shouldCheckForMfaUsing(fn (): bool => false);

    expect($this->service->shouldCheckForMfa($request, $user))->toBeFalse();
});

it('can get the preferred mfa method for a user', function () {
    $user = User::factory()->make();

    expect($this->service->preferredMfaMethodFor($user, []))->toBe(MfaChallengeMode::RecoveryCode->value)
        ->and($this->service->preferredMfaMethodFor($user, [MfaChallengeMode::App->value, MfaChallengeMode::Webauthn->value]))->toBe(MfaChallengeMode::App->value)
        ->and($this->service->preferredMfaMethodFor($user, [MfaChallengeMode::Webauthn->value]))->toBe(MfaChallengeMode::Webauthn->value);

    ProfileFilament::getPreferredMfaMethodUsing(fn (): string => 'foo');

    expect($this->service->preferredMfaMethodFor($user, []))->toBe('foo');
});

it('can get the preferred sudo challenge method for a user', function () {
    $user = User::factory()->withoutMfa()->make();

    $methods = [
        MfaChallengeMode::App->value,
    ];

    expect($this->service->preferredSudoChallengeMethodFor($user, $methods))->toBe(SudoChallengeMode::Password->value);

    $user->two_factor_enabled = true;

    ProfileFilament::getPreferredMfaMethodUsing(fn ($user, $availableMethods): string => $availableMethods[0]);

    expect($this->service->preferredSudoChallengeMethodFor($user, $methods))->toBe(MfaChallengeMode::App->value);
});

test('recovery code is not allowed for sudo challenge', function () {
    $user = User::factory()->withMfa()->make();
    ProfileFilament::getPreferredMfaMethodUsing(fn () => MfaChallengeMode::RecoveryCode->value);

    expect($this->service->preferredSudoChallengeMethodFor($user, []))->toBe(SudoChallengeMode::Password->value);
});

it('gets the pipes to send mfa challenges through for authentication', function () {
    expect($this->service->getMfaAuthenticationPipes())->toMatchArray([
        PrepareUserSession::class,
    ]);

    ProfileFilament::mfaAuthenticationPipelineUsing(fn (): array => ['one', 'two']);

    expect($this->service->getMfaAuthenticationPipes())->toMatchArray([
        'one',
        'two',
    ]);
});
