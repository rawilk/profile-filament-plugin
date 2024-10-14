<?php

declare(strict_types=1);

use PragmaRX\Google2FA\Google2FA;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeActivated;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Livewire\Sudo\SudoChallengeActionForm;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Services\Webauthn;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    $this->user = User::factory()
        ->withMfa()
        ->hasAuthenticatorApps()
        ->create([
            'id' => 1,
            'password' => 'secret',
        ]);

    WebauthnKey::factory()->for($this->user)->create([
        'credential_id' => FakeWebauthn::rawCredentialId(),
    ]);

    login($this->user);

    Route::webauthn();

    Webauthn::generateChallengeWith(fn () => FakeWebauthn::rawAssertionChallenge());
});

afterEach(function () {
    Webauthn::generateChallengeWith(null);
});

it('can set a new challenge mode', function (SudoChallengeMode $mode) {
    livewire(SudoChallengeActionForm::class)
        ->call('setChallengeMode', $mode->value)
        ->assertSet('challengeMode', $mode)
        ->assertSet('mode', $mode->value);
})->with([
    'totp' => SudoChallengeMode::App,
    'webauthn' => SudoChallengeMode::Webauthn,
    'password' => SudoChallengeMode::Password,
]);

it('shows a password input for password challenge mode', function () {
    livewire(SudoChallengeActionForm::class)
        ->call('setChallengeMode', SudoChallengeMode::Password->value)
        ->assertFormFieldIsVisible('password');
});

it('shows a totp code input for app challenge mode', function () {
    livewire(SudoChallengeActionForm::class)
        ->call('setChallengeMode', SudoChallengeMode::App->value)
        ->assertFormFieldIsVisible('totp');
});

it('shows the webauthn registration form when in webauthn challenge mode', function () {
    $this->freezeSecond();

    $expectedUrl = URL::temporarySignedRoute(
        name: 'profile-filament::webauthn.assertion_pk',
        expiration: now()->addHour(),
        parameters: [
            'user' => $this->user->getRouteKey(),
            's' => SudoSession::WebauthnAssertionPk->value,
        ],
    );

    livewire(SudoChallengeActionForm::class)
        ->call('setChallengeMode', SudoChallengeMode::Webauthn->value)
        ->assertSee(Js::from($expectedUrl), escape: false)
        ->assertActionVisible('startWebauthn')
        ->assertActionHidden('submit');
});

test('user identity can be confirmed with a password', function () {
    livewire(SudoChallengeActionForm::class)
        ->call('setChallengeMode', SudoChallengeMode::Password->value)
        ->fillForm([
            'password' => 'secret',
        ])
        ->call('confirm')
        ->assertHasNoFormErrors();

    expect(Sudo::isActive())->toBeTrue();

    Event::assertDispatched(function (SudoModeActivated $event) {
        expect($event->user)->toBe($this->user);

        return true;
    });
});

test('a correct password is required to confirm identity', function () {
    livewire(SudoChallengeActionForm::class)
        ->call('setChallengeMode', SudoChallengeMode::Password->value)
        ->fillForm([
            'password' => 'invalid',
        ])
        ->call('confirm')
        ->assertSet('error', __('profile-filament::messages.sudo_challenge.password.invalid'));

    expect(Sudo::isActive())->toBeFalse();

    Event::assertNotDispatched(SudoModeActivated::class);
});

it('can confirm identity with totp', function () {
    $this->freezeSecond();

    $mfaEngine = app(Google2FA::class);
    $userSecret = $mfaEngine->generateSecretKey();
    $validOtp = $mfaEngine->getCurrentOtp($userSecret);

    $app = AuthenticatorApp::factory()->for($this->user)->create(['secret' => $userSecret]);

    livewire(SudoChallengeActionForm::class)
        ->call('setChallengeMode', SudoChallengeMode::App->value)
        ->fillForm([
            'totp' => $validOtp,
        ])
        ->call('confirm')
        ->assertHasNoFormErrors();

    expect(Sudo::isActive())->toBeTrue();

    Event::assertDispatched(function (SudoModeActivated $event) {
        expect($event->user)->toBe($this->user);

        return true;
    });

    expect($app->refresh())->last_used_at->toBe(now());
});

test('a valid totp is required to confirm identity', function () {
    livewire(SudoChallengeActionForm::class)
        ->call('setChallengeMode', SudoChallengeMode::App->value)
        ->fillForm([
            'totp' => 'invalid',
        ])
        ->call('confirm')
        ->assertSet('error', __('profile-filament::messages.sudo_challenge.totp.invalid'));

    expect(Sudo::isActive())->toBeFalse();

    Event::assertNotDispatched(SudoModeActivated::class);
});

it('can use webauthn to confirm identity', function () {
    storeAssertionOptionsInSession($this->user);

    livewire(SudoChallengeActionForm::class)
        ->call('setChallengeMode', SudoChallengeMode::Webauthn->value)
        ->call('confirm', assertion: FakeWebauthn::assertionResponse())
        ->assertSuccessful()
        ->assertSessionMissing(SudoSession::WebauthnAssertionPk->value);

    expect(Sudo::isActive())->toBeTrue();

    Event::assertDispatched(function (SudoModeActivated $event) {
        expect($event->user)->toBe($this->user);

        return true;
    });
});

test('a valid webauthn assertion is required to confirm identity', function () {
    storeAssertionOptionsInSession($this->user);

    $assertion = FakeWebauthn::assertionResponse();
    data_set($assertion, 'response.clientDataJSON', 'invalid');

    livewire(SudoChallengeActionForm::class)
        ->call('setChallengeMode', SudoChallengeMode::Webauthn->value)
        ->call('confirm', assertion: $assertion)
        ->assertSet('error', __('profile-filament::messages.sudo_challenge.webauthn.invalid'))
        ->assertSet('hasWebauthnError', true);

    expect(Sudo::isActive())->toBeFalse();

    Event::assertNotDispatched(SudoModeActivated::class);
});
