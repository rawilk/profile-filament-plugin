<?php

declare(strict_types=1);

use PragmaRX\Google2FA\Google2FA;
use Rawilk\ProfileFilament\Enums\Livewire\MfaChallengeMode;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Filament\Pages\MfaChallenge;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\ProfileFilament;
use Rawilk\ProfileFilament\Services\Webauthn;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->withMfa()->create([
        'two_factor_recovery_codes' => Crypt::encryptString(
            json_encode([
                'code-one',
                'code-two',
                'code-three',
                'code-four',
            ])
        ),
    ]);

    session()->put(MfaSession::User->value, $this->user->getKey());

    Route::webauthn();

    Webauthn::generateChallengeWith(fn () => FakeWebauthn::rawAssertionChallenge());
});

afterEach(function () {
    ProfileFilament::getPreferredMfaMethodUsing(null);
    Webauthn::generateChallengeWith(null);
});

it('renders', function () {
    livewire(MfaChallenge::class)
        ->assertSuccessful()
        ->assertSet('user.id', $this->user->id);
});

it('redirects to login with no challenged user', function () {
    session()->forget(MfaSession::User->value);

    livewire(MfaChallenge::class)
        ->assertRedirect(filament()->getLoginUrl());
});

it('can be shown for an authenticated user', function () {
    session()->forget(MfaSession::User->value);

    login($this->user);

    livewire(MfaChallenge::class)
        ->assertNoRedirect()
        ->assertSessionHas(MfaSession::User->value, $this->user->id);
});

it('redirects when mfa is already confirmed', function () {
    Mfa::confirmUserSession($this->user);

    login($this->user);

    livewire(MfaChallenge::class)
        ->assertRedirect(filament()->getUrl());
});

it('initializes with a users preferred mfa challenge mode', function () {
    ProfileFilament::getPreferredMfaMethodUsing(fn () => MfaChallengeMode::App->value);

    livewire(MfaChallenge::class)
        ->assertSet('challengeMode', MfaChallengeMode::App);
});

it('can set a new challenge mode', function (MfaChallengeMode $mode) {
    livewire(MfaChallenge::class)
        ->call('setChallengeMode', $mode->value)
        ->assertSet('challengeMode', $mode)
        ->assertSet('mode', $mode->value);
})->with([
    'totp' => MfaChallengeMode::App,
    'webauthn' => MfaChallengeMode::Webauthn,
    'recovery codes' => MfaChallengeMode::RecoveryCode,
]);

it('can confirm identity with totp', function () {
    login($this->user);

    $this->freezeSecond();

    $mfaEngine = app(Google2FA::class);
    $userSecret = $mfaEngine->generateSecretKey();
    $validOtp = $mfaEngine->getCurrentOtp($userSecret);

    $app = AuthenticatorApp::factory()->for($this->user)->create([
        'secret' => $userSecret,
    ]);

    livewire(MfaChallenge::class)
        ->call('setChallengeMode', MfaChallengeMode::App->value)
        ->assertFormFieldIsVisible('totp')
        ->fillForm([
            'totp' => $validOtp,
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect(filament()->getUrl());

    expect($app->refresh())->last_used_at->toBe(now())
        ->and($this->user)->isMfaConfirmed();
});

test('a valid totp is required to confirm identity', function () {
    login($this->user);

    livewire(MfaChallenge::class)
        ->call('setChallengeMode', MfaChallengeMode::App->value)
        ->assertFormFieldIsVisible('totp')
        ->fillForm([
            'totp' => 'invalid',
        ])
        ->call('authenticate')
        ->assertSet('error', __('profile-filament::pages/mfa.totp.invalid'))
        ->assertNoRedirect();

    expect($this->user)->not->isMfaConfirmed();
});

it('can confirm identity with a recovery code', function () {
    login($this->user);

    livewire(MfaChallenge::class)
        ->call('setChallengeMode', MfaChallengeMode::RecoveryCode->value)
        ->assertFormFieldIsVisible('code')
        ->fillForm([
            'code' => 'code-three',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    // Code should be consumed
    expect(Mfa::usingChallengedUser($this->user->refresh())->isValidRecoveryCode('code-three'))->toBeFalse()
        ->and($this->user)->isMfaConfirmed();
});

it('requires a valid recovery code', function () {
    login($this->user);

    livewire(MfaChallenge::class)
        ->call('setChallengeMode', MfaChallengeMode::RecoveryCode->value)
        ->fillForm([
            'code' => 'code-five',
        ])
        ->call('authenticate')
        ->assertSet('error', __('profile-filament::pages/mfa.recovery_code.invalid'));

    expect($this->user)->not->isMfaConfirmed();
});

it('can confirm identity with webauthn', function () {
    $this->freezeSecond();

    login($this->user);

    $key = WebauthnKey::factory()->for($this->user)->create([
        'credential_id' => FakeWebauthn::rawCredentialId(),
    ]);

    storeAssertionOptionsInSession($this->user, sessionKey: MfaSession::AssertionPublicKey->value);

    livewire(MfaChallenge::class)
        ->call('setChallengeMode', MfaChallengeMode::Webauthn->value)
        ->call('authenticate', assertion: FakeWebauthn::assertionResponse())
        ->assertSuccessful()
        ->assertSessionMissing(MfaSession::AssertionPublicKey->value);

    expect($key->refresh())->last_used_at->toBe(now())
        ->and($this->user)->isMfaConfirmed();
});

test('a valid webauthn assertion is required to confirm identity', function () {
    $this->freezeSecond();

    login($this->user);

    $key = WebauthnKey::factory()->for($this->user)->create([
        'credential_id' => FakeWebauthn::rawCredentialId(),
    ]);

    $assertion = FakeWebauthn::assertionResponse();
    data_set($assertion, 'response.clientDataJSON', 'invalid');

    livewire(MfaChallenge::class)
        ->call('setChallengeMode', MfaChallengeMode::Webauthn->value)
        ->call('authenticate', assertion: $assertion)
        ->assertSet('error', __('profile-filament::pages/mfa.webauthn.assert.failure'))
        ->assertSet('hasWebauthnError', true);

    expect($key->refresh())->last_used_at->toBeNull()
        ->and($this->user)->not->isMfaConfirmed();
});
