<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Actions\Webauthn\RegisterWebauthnKeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
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

    login($this->user = User::factory()->withoutMfa()->create(['id' => 1]));

    Webauthn::generateChallengeWith(fn () => FakeWebauthn::rawAttestationChallenge());
});

afterEach(function () {
    Webauthn::generateChallengeWith(null);
});

it('renders', function () {
    livewire(WebauthnKeys::class, [
        'webauthnKeys' => collect(),
    ])
        ->assertSuccessful();
});

it('can show a form to add webauthn keys', function () {
    livewire(WebauthnKeys::class, [
        'show' => true,
        'webauthnKeys' => collect(),
    ])
        ->assertSet('showForm', false)
        ->assertDontSeeHtml('data-test="register-form"')
        ->callAction('add')
        ->assertSet('showForm', true)
        ->assertSeeHtml('data-test="register-form"');
});

it('can register a new webauthn key to the user', function () {
    // Simulate call to controller to create the options object.
    storeAttestationOptionsInSession($this->user);

    livewire(WebauthnKeys::class, [
        'webauthnKeys' => collect(),
        'show' => true,
        'showForm' => true,
    ])
        ->fillForm([
            'name' => 'my key',
        ])
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertHasNoFormErrors()
        ->assertSessionMissing(MfaSession::AttestationPublicKey->value)
        ->assertDispatched(MfaEvent::WebauthnKeyAdded->value, enabledMfa: true);

    Event::assertDispatched(WebauthnKeyRegistered::class);

    $this->assertDatabaseHas(WebauthnKey::class, [
        'user_id' => $this->user->getKey(),
        'name' => 'my key',
    ]);
});

test('a valid challenge is required to save a new key', function () {
    // Simulate call to controller to create the options object.
    storeAttestationOptionsInSession($this->user);

    $attestation = FakeWebauthn::attestationResponse();
    data_set($attestation, 'response.clientDataJSON', 'invalid');

    livewire(WebauthnKeys::class, [
        'webauthnKeys' => collect(),
        'show' => true,
        'showForm' => true,
    ])
        ->assertSessionHas(MfaSession::AttestationPublicKey->value)
        ->fillForm([
            'name' => 'my key',
        ])
        ->call('verifyKey', attestation: $attestation)
        ->assertNotDispatched(MfaEvent::WebauthnKeyAdded->value)
        ->assertSessionMissing(MfaSession::AttestationPublicKey->value);

    Event::assertNotDispatched(WebauthnKeyRegistered::class);

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'user_id' => $this->user->getKey(),
    ]);
});

test('sudo mode can be required to register a new webauthn key', function () {
    enableSudoMode();

    $component = livewire(WebauthnKeys::class, [
        'webauthnKeys' => collect(),
        'show' => true,
        'showForm' => true,
    ]);

    $component->call('mountAction', 'add')
        ->assertSeeText(sudoChallengeTitle());

    storeAttestationOptionsInSession($this->user);

    $component
        ->fillForm([
            'name' => 'my key',
        ])
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertSeeText(sudoChallengeTitle());

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'user_id' => $this->user->getKey(),
    ]);
});

test('key name is required', function () {
    storeAttestationOptionsInSession($this->user);

    livewire(WebauthnKeys::class, [
        'webauthnKeys' => collect(),
        'show' => true,
        'showForm' => true,
    ])
        ->fillForm([
            'name' => null,
        ])
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertHasFormErrors([
            'name' => ['required'],
        ])
        ->assertSessionHas(MfaSession::AttestationPublicKey->value);

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'user_id' => $this->user->getKey(),
    ]);
});

test('a unique key name is required', function () {
    WebauthnKey::factory()->for($this->user)->create(['name' => 'taken name']);

    storeAttestationOptionsInSession($this->user);

    livewire(WebauthnKeys::class, [
        'webauthnKeys' => collect(),
        'show' => true,
        'showForm' => true,
    ])
        ->fillForm([
            'name' => 'taken name',
        ])
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertHasFormErrors([
            'name' => ['unique'],
        ])
        ->assertSessionHas(MfaSession::AttestationPublicKey->value);

    $this->assertDatabaseCount(WebauthnKey::class, 1);
});

it('shows the registration form automatically when the user has no registered keys', function () {
    livewire(WebauthnKeys::class, [
        'webauthnKeys' => collect(),
    ])
        ->call('toggle', show: true)
        ->assertSet('showForm', true);
});

it('does not show the registration form automatically when the user has registered keys', function () {
    $keys = WebauthnKey::factory()->for($this->user)->count(1)->create();

    livewire(WebauthnKeys::class, [
        'webauthnKeys' => $keys,
    ])
        ->call('toggle', show: true)
        ->assertSet('showForm', false);
});

it('shows a users registered keys in descending order', function () {
    $this->freezeSecond();

    $keys = WebauthnKey::factory()
        ->sequence(
            ['created_at' => now(), 'name' => 'key.one'],
            ['created_at' => now()->subSecond(), 'name' => 'key.two'],
            ['created_at' => now()->addSecond(), 'name' => 'key.three'],
        )
        ->for($this->user)
        ->count(3)
        ->create();

    livewire(WebauthnKeys::class, [
        'webauthnKeys' => $keys,
        'show' => true,
    ])
        ->assertSeeTextInOrder([
            'key.three',
            'key.one',
            'key.two',
        ]);
});
