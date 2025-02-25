<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use PragmaRX\Google2FA\Google2FA;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppUsed;
use Rawilk\ProfileFilament\Events\RecoveryCodeReplaced;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationChallenged;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Services\Mfa;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->withMfa()->create(['id' => 1]);

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

    session()->put(MfaSession::User->value, -1);

    expect($this->mfa->hasChallengedUser())->toBeFalse();
});

it('can confirm a user mfa session', function () {
    challengeUser();

    $this->mfa->confirmUserSession($this->user);

    expect(session()->has(MfaSession::User->value))->toBeFalse()
        ->and(session()->get(MfaSession::Confirmed->value . '.1'))->toBeTrue();
});

it('knows if a users session has confirmed mfa', function () {
    $this->mfa->confirmUserSession($this->user);

    expect($this->mfa->isConfirmedInSession($this->user))->toBeTrue()
        ->and($this->mfa->isConfirmedInSession(User::factory()->withMfa()->create()))->toBeFalse();
});

it('can determine if a recovery code is valid', function () {
    challengeUser();

    $this->user->update([
        'two_factor_recovery_codes' => Crypt::encryptString(
            json_encode([
                'code-one',
                'code-two',
                'code-three',
                'code-four',
            ])
        ),
    ]);

    expect($this->mfa->isValidRecoveryCode('code-two'))->toBeTrue();

    Event::assertDispatched(function (RecoveryCodeReplaced $event) {
        expect($event->oldCode)->toBe('code-two')
            ->and($event->user)->toBe($this->user);

        return true;
    });

    expect($this->user->refresh())->recoveryCodes()->not->toContain('code-two');
});

it('handles an invalid recovery code', function () {
    challengeUser();

    expect($this->mfa->isValidRecoveryCode('invalid'))->toBeFalse();

    Event::assertNotDispatched(RecoveryCodeReplaced::class);
});

it('can determine if a totp code is valid', function () {
    challengeUser();

    $this->freezeSecond();

    $mfaEngine = app(Google2FA::class);
    $userSecret = $mfaEngine->generateSecretKey();
    $validOtp = $mfaEngine->getCurrentOtp($userSecret);

    $apps = AuthenticatorApp::factory()
        ->for($this->user)
        ->sequence(
            ['secret' => $mfaEngine->generateSecretKey()],
            ['secret' => $userSecret],
        )
        ->count(2)
        ->create();

    expect($this->mfa->isValidTotpCode($validOtp))->toBeTrue()
        ->and($apps->first()->refresh())->last_used_at->toBeNull()
        ->and($apps->last()->refresh())->last_used_at->toBe(now());

    Event::assertDispatched(function (TwoFactorAppUsed $event) use ($apps) {
        expect($event->user)->toBe($this->user)
            ->and($event->authenticatorApp)->toBe($apps->last());

        return true;
    });
});

it('handles invalid totp codes', function () {
    challengeUser();

    AuthenticatorApp::factory()->for($this->user)->create();

    expect($this->mfa->isValidTotpCode('invalid'))->toBeFalse();

    Event::assertNotDispatched(TwoFactorAppUsed::class);
});

it('knows if a remember cookie should be set', function () {
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

it('can push a challenged user to the session', function () {
    expect(session()->has(MfaSession::User->value))->toBeFalse();

    $this->mfa->pushChallengedUser(user: $this->user, remember: true);

    expect(session()->get(MfaSession::User->value))->toBe($this->user->getKey())
        ->and(session()->get(MfaSession::Remember->value))->toBeTrue();

    Event::assertDispatched(TwoFactorAuthenticationChallenged::class);
});

it('can determine if a user has mfa enabled on their account', function () {
    expect($this->mfa->userHasMfaEnabled($this->user))->toBeTrue();

    $this->user->update(['two_factor_enabled' => false]);

    expect($this->mfa->userHasMfaEnabled($this->user))->toBeFalse();
});

it('checks for a "hasTwoFactorEnabled" method on a user model', function () {
    $model = new class extends User
    {
        protected $table = 'users';

        public function hasTwoFactorEnabled(): bool
        {
            return false;
        }
    };

    $customUser = $model::find($this->user->getKey());
    $customUser->update(['two_factor_enabled' => true]);

    expect($this->mfa->userHasMfaEnabled($customUser))->toBeFalse();
});
