<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Actions\Webauthn\RegisterWebauthnKeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyRegistered;
use Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\WebauthnKeys;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Services\Webauthn;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();
    Route::webauthn();

    disableSudoMode();

    config([
        'profile-filament.actions.mark_two_factor_enabled' => MarkTwoFactorEnabledAction::class,
        'profile-filament.actions.register_webauthn_key' => RegisterWebauthnKeyAction::class,
    ]);

    login($this->user = User::factory()->withoutMfa()->create());
});

it('can show a form to add webauthn keys', function () {
    livewire(WebauthnKeys::class, ['webauthnKeys' => collect(), 'show' => true])
        ->assertSuccessful()
        ->assertSet('showForm', false)
        ->assertDontSee('data.name')
        ->assertActionExists('add')
        ->callAction('add')
        ->assertSet('showForm', true)
        ->assertSee('data.name');
});

it('can register a new webauthn key to the user', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    // Simulate call to controller
    storeAttestationPublicKeyInSession($this->user);

    livewire(WebauthnKeys::class, ['webauthnKeys' => collect(), 'show' => true])
        ->callAction('add')
        ->set('data.name', 'my key')
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertSuccessful()
        ->assertSessionMissing(MfaSession::AttestationPublicKey->value)
        ->assertNotified(__('profile-filament::pages/security.mfa.webauthn.actions.register.success'))
        ->assertDispatched(MfaEvent::WebauthnKeyAdded->value, enabledMfa: true, keyId: 1)
        ->assertSet('showForm', false);

    Event::assertDispatched(WebauthnKeyRegistered::class);
    Event::assertDispatched(TwoFactorAuthenticationWasEnabled::class);

    $this->assertDatabaseHas(WebauthnKey::class, [
        'user_id' => $this->user->id,
        'name' => 'my key',
    ]);
});

test('a valid challenge is required to save a new key', function () {
    // Simulate call to controller
    storeAttestationPublicKeyInSession($this->user);

    $attestation = FakeWebauthn::attestationResponse();
    $attestation['response']['clientDataJSON'] = 'invalid';

    livewire(WebauthnKeys::class, ['webauthnKeys' => collect(), 'show' => true])
        ->callAction('add')
        ->assertSessionHas(MfaSession::AttestationPublicKey->value)
        ->set('data.name', 'my key')
        ->call('verifyKey', attestation: $attestation)
        ->assertNotified(__('profile-filament::pages/security.mfa.webauthn.actions.register.register_fail_notification'))
        ->assertNotDispatched(MfaEvent::WebauthnKeyAdded->value)
        ->assertSessionMissing(MfaSession::AttestationPublicKey->value);

    Event::assertNotDispatched(WebauthnKeyRegistered::class);

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'user_id' => $this->user->id,
    ]);
});

test('sudo mode is required to add a new key', function () {
    enableSudoMode();

    livewire(WebauthnKeys::class, ['webauthnKeys' => collect(), 'show' => true])
        ->call('mountAction', 'add')
        ->assertActionMounted('sudoChallenge');

    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAssertionChallenge());
    storeAttestationPublicKeyInSession($this->user);

    livewire(WebauthnKeys::class, ['webauthnKeys' => collect(), 'show' => true, 'showForm' => true])
        ->set('data.name', 'my key')
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertActionMounted('sudoChallenge');
});

test('key name is required', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    // Simulate call to controller
    storeAttestationPublicKeyInSession($this->user);

    livewire(WebauthnKeys::class, ['webauthnKeys' => collect(), 'show' => true])
        ->callAction('add')
        ->set('data.name', '')
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertHasErrors(['data.name' => 'required'])
        // Form should be validated before we verify the key
        ->assertSessionHas(MfaSession::AttestationPublicKey->value)
        ->assertNotDispatched(MfaEvent::WebauthnKeyAdded->value);

    Event::assertNotDispatched(WebauthnKeyRegistered::class);

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'user_id' => $this->user->id,
    ]);
});

test('a unique key name is required', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    WebauthnKey::factory()->for($this->user)->create(['name' => 'taken name']);

    // Simulate call to controller
    storeAttestationPublicKeyInSession($this->user);

    livewire(WebauthnKeys::class, ['webauthnKeys' => collect(), 'show' => true])
        ->callAction('add')
        ->set('data.name', 'taken name')
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertHasErrors(['data.name' => 'unique'])
        // Form should be validated before we verify the key
        ->assertSessionHas(MfaSession::AttestationPublicKey->value)
        ->assertNotDispatched(MfaEvent::WebauthnKeyAdded->value);

    Event::assertNotDispatched(WebauthnKeyRegistered::class);

    $this->assertDatabaseCount(WebauthnKey::class, 1);
});

it('shows the registration form when no webauthn keys are registered to the user', function () {
    livewire(WebauthnKeys::class, ['webauthnKeys' => collect()])
        ->call('toggle', show: true)
        ->assertSet('showForm', true);
});

it('does not show the registration form if the user already has webauthn keys registered', function () {
    $keys = WebauthnKey::factory()->for($this->user)->count(1)->create();

    livewire(WebauthnKeys::class, ['webauthnKeys' => $keys])
        ->call('toggle', show: true)
        ->assertSet('showForm', false);
});

it("shows a user's registered keys in descending order", function () {
    $keys = WebauthnKey::factory()
        ->state(new Sequence(
            ['created_at' => now(), 'name' => 'key--one'],
            ['created_at' => now()->subSecond(), 'name' => 'key--two'],
            ['created_at' => now()->addSecond(), 'name' => 'key--three'],
        ))
        ->for($this->user)
        ->count(3)
        ->create();

    livewire(WebauthnKeys::class, ['webauthnKeys' => $keys, 'show' => true])
        ->assertSeeInOrder([
            'key--three',
            'key--one',
            'key--two',
        ]);
});
