<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use PragmaRX\Google2FA\Google2FA;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppUsed;
use Rawilk\ProfileFilament\Events\RecoveryCodeReplaced;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Services\Mfa;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException as HttpException;

beforeEach(function () {
    $this->user = User::factory()->withMfa()->create();

    $this->mfa = new Mfa(userModel: User::class);

    Event::fake();
});

it('can get the challenged user', function () {
    challengeUser();

    $user = $this->mfa->challengedUser();

    expect($user)->toBe($this->user);
});

it('caches the challenged user for subsequent calls', function () {
    trackQueries();
    challengeUser();

    $this->mfa->challengedUser();
    $this->mfa->challengedUser();

    expect(1)->toBeQueryCount();
});

it('aborts if the challenged user cannot be found', function () {
    session()->put(MfaSession::User->value, 2);

    $this->mfa->challengedUser();
})->throws(HttpException::class, 'Your user account could not be verified.');

it('knows if there is a challenged user', function () {
    expect($this->mfa->hasChallengedUser())->toBeFalse();

    challengeUser();

    expect($this->mfa->hasChallengedUser())->toBeTrue();

    session()->put(MfaSession::User->value, 2);

    expect($this->mfa->hasChallengedUser())->toBeFalse();
});

it('can confirm a user mfa session', function () {
    challengeUser();

    $this->mfa->confirmUserSession($this->user);

    expect(session()->has(MfaSession::User->value))->toBeFalse()
        ->and(session()->get(MfaSession::Confirmed->value . '.1'))->toBeTrue();
});

it('knows if a user session has been mfa confirmed', function () {
    $this->mfa->confirmUserSession($this->user);

    expect($this->mfa->isConfirmedInSession($this->user))->toBeTrue()
        ->and($this->mfa->isConfirmedInSession(User::factory()->withMfa()->create()))->toBeFalse();
});

it('can determine if a recovery code is valid', function () {
    challengeUser();

    $validCode = $this->user->recoveryCodes()[0];

    expect($this->mfa->isValidRecoveryCode($validCode))->toBeTrue();

    Event::assertDispatched(function (RecoveryCodeReplaced $event) use ($validCode) {
        expect($event->oldCode)->toBe($validCode)
            ->and($event->user)->toBe($this->user);

        return true;
    });

    // Same code cannot be used twice
    expect($this->mfa->isValidRecoveryCode($validCode))->toBeFalse();
});

it('can determine if a totp code is valid', function () {
    challengeUser();

    Date::setTestNow('2023-01-01 10:00:00');

    $mfaEngine = app(Google2FA::class);
    $userSecret = $mfaEngine->generateSecretKey();
    $validOtp = $mfaEngine->getCurrentOtp($userSecret);

    AuthenticatorApp::factory()->for($this->user)->create();

    $authenticator = AuthenticatorApp::factory()->for($this->user)->create([
        'secret' => $userSecret,
    ]);

    expect($this->mfa->isValidTotpCode($validOtp))->toBeTrue()
        ->and($authenticator->refresh())->last_used_at->toDateTimeString()->toBe('2023-01-01 10:00:00');

    Event::assertDispatched(function (TwoFactorAppUsed $event) use ($authenticator) {
        expect($event->user)->toBe($this->user)
            ->and($event->authenticatorApp)->toBe($authenticator);

        return true;
    });
});

it('handles invalid totp codes', function () {
    challengeUser();

    AuthenticatorApp::factory()->for($this->user)->create();

    expect($this->mfa->isValidTotpCode('invalid'))->toBeFalse();

    Event::assertNotDispatched(TwoFactorAppUsed::class);
});

it('knows if remember cookie should be set', function () {
    expect($this->mfa->remember())->toBeFalse();

    (fn () => $this->remember = null)->call($this->mfa);

    session()->put(MfaSession::Remember->value, true);

    expect($this->mfa->remember())->toBeTrue()
        ->and(session()->has(MfaSession::Remember->value))->toBeFalse();
});

it('knows if a user has totp apps registered', function () {
    expect($this->mfa->canUseAuthenticatorAppsForChallenge($this->user))->toBeFalse();

    AuthenticatorApp::factory()->for($this->user)->create();

    expect($this->mfa->canUseAuthenticatorAppsForChallenge($this->user))->toBeTrue();
});

it('knows if a user has webauthn keys registered', function () {
    expect($this->mfa->canUseWebauthnForChallenge($this->user))->toBeFalse();

    WebauthnKey::factory()->for($this->user)->create();

    expect($this->mfa->canUseWebauthnForChallenge($this->user))->toBeTrue();
});

function challengeUser(): void
{
    session()->put(MfaSession::User->value, test()->user->id);
}
