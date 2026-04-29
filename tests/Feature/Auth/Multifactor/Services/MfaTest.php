<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Enums\MfaSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Events\MultiFactorAuthenticationChallengeWasPresented;
use Rawilk\ProfileFilament\Auth\Multifactor\Services\Mfa;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = app(Mfa::class);
});

it('can get the challenged user', function () {
    challengeUser($this->user);

    $user = $this->service->challengedUser();

    expect($user)->toBe($this->user);
});

it('caches the challenged user for subsequent calls', function () {
    trackQueries();
    challengeUser($this->user);

    $this->service->challengedUser();
    $this->service->challengedUser();

    expect(1)->toBeQueryCount();
});

it('can confirm a user mfa session', function () {
    $this->freezeSecond();

    challengeUser($this->user);

    $this->service->confirmUserSession($this->user);

    $sessionKey = Str::of(MfaSession::ConfirmedAt->value)
        ->append("__{$this->user->getAuthIdentifier()}")
        ->value();

    expect(MfaSession::UserBeingAuthenticated->has())->toBeFalse()
        ->and(MfaSession::PasswordConfirmedAt->has())->toBeFalse()
        ->and(session()->get($sessionKey))->toBe(now()->unix());
});

it('knows if a user requested to be remembered', function (bool $condition) {
    MfaSession::Remember->set($condition);

    expect($this->service->remember())->toBe($condition);
})->with([true, false]);

it('can set a user to be challenged in the session', function () {
    Event::fake();
    $this->freezeSecond();

    $this->service->pushChallengedUser($this->user, remember: true);

    expect(MfaSession::UserBeingAuthenticated->get())->toBe((string) $this->user->getAuthIdentifier())
        ->and(MfaSession::Remember->isTrue())->toBeTrue()
        ->and(MfaSession::PasswordConfirmedAt->get())->toBe(now()->unix());

    Event::assertDispatched(MultiFactorAuthenticationChallengeWasPresented::class);
});

it('can determine if too much time has passed since the user confirmed their password', function () {
    $this->freezeSecond();

    expect($this->service->passwordConfirmationHasExpired())->toBeTrue();

    MfaSession::PasswordConfirmedAt->set(now()->unix());

    $this->travel(15)->minutes();

    expect($this->service->passwordConfirmationHasExpired())->toBeFalse();

    $this->travel(1)->second();

    expect($this->service->passwordConfirmationHasExpired())->toBeTrue();
});
