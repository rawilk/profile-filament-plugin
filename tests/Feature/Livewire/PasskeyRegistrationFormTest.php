<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\Passkeys\RegisterPasskeyAction;
use Rawilk\ProfileFilament\Actions\Passkeys\UpgradeToPasskeyAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyRegistered;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUpgradeToPasskey;
use Rawilk\ProfileFilament\Livewire\PasskeyRegistrationForm;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Services\Webauthn;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();
    Route::webauthn();

    login($this->user = User::factory()->withoutMfa()->create(['id' => 1]));

    disableSudoMode();

    Webauthn::generateChallengeWith(fn () => FakeWebauthn::rawAttestationChallenge());

    config([
        'profile-filament.actions.register_passkey' => RegisterPasskeyAction::class,
        'profile-filament.actions.mark_two_factor_enabled' => MarkTwoFactorEnabledAction::class,
        'profile-filament.actions.upgrade_to_passkey' => UpgradeToPasskeyAction::class,
    ]);
});

afterEach(function () {
    Webauthn::generateChallengeWith(null);
});

it('renders', function () {
    livewire(PasskeyRegistrationForm::class)
        ->assertSuccessful();
});

it('registers a passkey to a user', function () {
    storePasskeyAttestationOptionsInSession($this->user);

    livewire(PasskeyRegistrationForm::class)
        ->fillForm([
            'name' => 'my passkey',
        ])
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertHasNoFormErrors()
        ->assertSessionMissing(MfaSession::PasskeyAttestationPk->value)
        ->assertDispatched(MfaEvent::PasskeyRegistered->value, name: 'my passkey', enabledMfa: true)
        ->assertNotDispatched(MfaEvent::WebauthnKeyUpgradedToPasskey->value);

    Event::assertDispatched(PasskeyRegistered::class);

    $this->assertDatabaseHas(WebauthnKey::class, [
        'user_id' => $this->user->getKey(),
        'name' => 'my passkey',
        'is_passkey' => true,
    ]);
});

it('can upgrade a webauthn key to a passkey', function () {
    $record = WebauthnKey::factory()->upgradeableToPasskey()->for($this->user)->create();

    storePasskeyAttestationOptionsInSession($this->user);

    livewire(PasskeyRegistrationForm::class, [
        'upgrading' => $record,
    ])
        ->assertSeeText(__('profile-filament::pages/security.passkeys.actions.upgrade.cancel_upgrade'))
        ->assertFormFieldIsHidden('name')
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertHasNoFormErrors()
        ->assertSessionMissing(MfaSession::PasskeyAttestationPk->value)
        ->assertDispatched(MfaEvent::WebauthnKeyUpgradedToPasskey->value, upgradedFrom: $record->getKey())
        ->assertSet('upgrading', null);

    $this->assertModelMissing($record);

    Event::assertDispatched(WebauthnKeyUpgradeToPasskey::class);

    $this->assertDatabaseHas(WebauthnKey::class, [
        'name' => $record->name,
        'user_id' => $this->user->getKey(),
        'is_passkey' => true,
    ]);
});

it('can cancel an upgrade', function () {
    $record = WebauthnKey::factory()->upgradeableToPasskey()->for($this->user)->create();

    livewire(PasskeyRegistrationForm::class, [
        'upgrading' => $record,
    ])
        ->call('cancelUpgrade')
        ->assertSet('upgrading', null)
        ->assertFormFieldIsVisible('name');
});

test('a valid challenge is required to save a new passkey', function () {
    storePasskeyAttestationOptionsInSession($this->user);

    $attestation = FakeWebauthn::attestationResponse();
    data_set($attestation, 'response.clientDataJSON', 'invalid');

    livewire(PasskeyRegistrationForm::class)
        ->assertSessionHas(MfaSession::PasskeyAttestationPk->value)
        ->fillForm([
            'name' => 'my passkey',
        ])
        ->call('verifyKey', attestation: $attestation)
        ->assertNotDispatched(MfaEvent::PasskeyRegistered->value)
        ->assertSessionMissing(MfaSession::AttestationPublicKey->value);
});

test('sudo mode can be required to register a new passkey', function () {
    enableSudoMode();

    storePasskeyAttestationOptionsInSession($this->user);

    livewire(PasskeyRegistrationForm::class)
        ->fillForm([
            'name' => 'my passkey',
        ])
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertSessionHas(MfaSession::PasskeyAttestationPk->value);

    Event::assertNotDispatched(PasskeyRegistered::class);
});

it('requires a passkey name to register', function () {
    storePasskeyAttestationOptionsInSession($this->user);

    livewire(PasskeyRegistrationForm::class)
        ->fillForm([
            'name' => null,
        ])
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertHasFormErrors([
            'name' => ['required'],
        ])
        ->assertNotDispatched(MfaEvent::PasskeyRegistered->value);
});

it('requires a unique name', function () {
    WebauthnKey::factory()->notPasskey()->for($this->user)->create(['name' => 'taken name']);

    storePasskeyAttestationOptionsInSession($this->user);

    livewire(PasskeyRegistrationForm::class)
        ->fillForm([
            'name' => 'taken name',
        ])
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertHasFormErrors([
            'name' => ['unique'],
        ])
        ->assertNotDispatched(MfaEvent::PasskeyRegistered->value);
});

it('resets upgrade status when a webauthn key is deleted', function () {
    $record = WebauthnKey::factory()->notPasskey()->for($this->user)->create();

    livewire(PasskeyRegistrationForm::class, [
        'upgrading' => $record,
    ])
        ->dispatch(MfaEvent::WebauthnKeyDeleted->value)
        ->assertSet('upgrading', null)
        ->assertSet('isUpgrading', false);
});

it('requires eligible keys to upgrade', function (WebauthnKey $record) {
    storePasskeyAttestationOptionsInSession($this->user);

    livewire(PasskeyRegistrationForm::class, [
        'upgrading' => $record,
    ])
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertNotDispatched(MfaEvent::WebauthnKeyUpgradedToPasskey->value);

    $this->assertModelExists($record);
})->with([
    'passkey' => fn () => WebauthnKey::factory()->passkey()->for(test()->user)->create(),
    'hardware key' => fn () => WebauthnKey::factory()->notUpgradeableToPasskey()->for(test()->user)->create(),
]);
