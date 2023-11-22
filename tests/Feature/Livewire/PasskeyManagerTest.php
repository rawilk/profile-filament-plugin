<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Rawilk\ProfileFilament\Actions\Passkeys\RegisterPasskeyAction;
use Rawilk\ProfileFilament\Actions\Passkeys\UpgradeToPasskeyAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyRegistered;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUpgradeToPasskey;
use Rawilk\ProfileFilament\Livewire\PasskeyManager;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Services\Webauthn;
use Rawilk\ProfileFilament\Testing\Support\FakeWebauthn;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();
    Route::webauthn();

    Webauthn::generateChallengeWith(null);

    disableSudoMode();

    config([
        'profile-filament.actions.register_passkey' => RegisterPasskeyAction::class,
        'profile-filament.actions.mark_two_factor_enabled' => MarkTwoFactorEnabledAction::class,
        'profile-filament.actions.upgrade_to_passkey' => UpgradeToPasskeyAction::class,
    ]);

    login($this->user = User::factory()->withoutMfa()->create());
});

it('can be rendered', function () {
    livewire(PasskeyManager::class)
        ->assertSuccessful()
        ->assertSeeText(__('profile-filament::pages/security.passkeys.empty_heading'));
});

it('shows a form to add a new passkey', function () {
    livewire(PasskeyManager::class)
        ->mountAction('add')
        ->assertSeeText(__('profile-filament::pages/security.passkeys.actions.add.modal_title'))
        ->assertSet('name', null)
        ->assertFormFieldIsVisible('name')
        ->assertDontSee(__('profile-filament::pages/security.passkeys.actions.upgrade.cancel_upgrade'));
});

it('shows a form to upgrade a webauthn key to a passkey', function () {
    $webauthnKey = WebauthnKey::factory()->upgradeableToPasskey()->for($this->user)->create();

    livewire(PasskeyManager::class)
        ->dispatch(MfaEvent::StartPasskeyUpgrade->value, id: $webauthnKey->id)
        ->assertActionMounted('add')
        ->assertSeeText(__('profile-filament::pages/security.passkeys.actions.upgrade.modal_title'))
        ->assertFormFieldIsHidden('name')
        ->assertSee(__('profile-filament::pages/security.passkeys.actions.upgrade.prompt_button'))
        ->assertSee(__('profile-filament::pages/security.passkeys.actions.upgrade.cancel_upgrade'));
});

it('can register a new passkey for a user', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    // Simulate call to controller.
    storeAttestationPublicKeyInSession($this->user, MfaSession::PasskeyAttestationPk->value);

    livewire(PasskeyManager::class)
        ->set('name', 'my key')
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertSuccessful()
        ->assertNotified()
        ->assertSessionMissing(MfaSession::PasskeyAttestationPk->value)
        ->assertDispatched(MfaEvent::PasskeyRegistered->value, id: 1, name: 'my key', enabledMfa: true);

    Event::assertDispatched(function (PasskeyRegistered $event) {
        expect($event->passkey->name)->toBe('my key');

        return true;
    });

    Event::assertDispatched(TwoFactorAuthenticationWasEnabled::class);

    $this->assertDatabaseHas(WebauthnKey::class, [
        'user_id' => $this->user->id,
        'name' => 'my key',
        'is_passkey' => true,
    ]);
});

test('sudo mode is required to register a new passkey', function () {
    enableSudoMode();

    livewire(PasskeyManager::class)
        ->call('mountAction', 'add')
        ->assertActionMounted('sudoChallenge');

    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());
    storeAttestationPublicKeyInSession($this->user, MfaSession::PasskeyAttestationPk->value);

    livewire(PasskeyManager::class)
        ->set('name', 'my key')
        ->call('verifyKey', FakeWebauthn::attestationResponse())
        ->assertActionMounted('sudoChallenge')
        ->assertNotDispatched(MfaEvent::PasskeyRegistered->value);

    Event::assertNotDispatched(PasskeyRegistered::class);

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'name' => 'my key',
    ]);
});

it('can upgrade a webauthn key to a passkey', function () {
    $webauthnKey = WebauthnKey::factory()->upgradeableToPasskey()->for($this->user)->create(['name' => 'not a passkey']);

    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    // Simulate call to controller.
    storeAttestationPublicKeyInSession($this->user, MfaSession::PasskeyAttestationPk->value);

    livewire(PasskeyManager::class)
        ->dispatch(MfaEvent::StartPasskeyUpgrade->value, id: $webauthnKey->id)
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertSuccessful()
        ->assertNotified()
        ->assertSessionMissing(MfaSession::PasskeyAttestationPk->value)
        ->assertDispatched(MfaEvent::WebauthnKeyUpgradedToPasskey->value, id: 2, upgradedFrom: 1)
        ->assertSet('upgrading', null);

    Event::assertDispatched(function (WebauthnKeyUpgradeToPasskey $event) {
        expect($event->passkey->name)->toBe('not a passkey');

        return true;
    });

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'id' => $webauthnKey->id,
    ]);

    $this->assertDatabaseHas(WebauthnKey::class, [
        'user_id' => $this->user->id,
        'name' => 'not a passkey',
        'is_passkey' => true,
    ]);
});

it('does not allow passkeys to upgrade', function () {
    $passkey = WebauthnKey::factory()->passkey()->for($this->user)->create();

    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    // Simulate call to controller.
    storeAttestationPublicKeyInSession($this->user, MfaSession::PasskeyAttestationPk->value);

    livewire(PasskeyManager::class)
        ->dispatch(MfaEvent::StartPasskeyUpgrade->value, id: $passkey->id)
        ->call('verifyKey', attestation: FakeWebauthn::attestationResponse())
        ->assertForbidden()
        ->assertSessionHas(MfaSession::PasskeyAttestationPk->value);
});

it('requires a key name to register', function () {
    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    // Simulate call to controller.
    storeAttestationPublicKeyInSession($this->user, MfaSession::PasskeyAttestationPk->value);

    livewire(PasskeyManager::class)
        ->call('verifyKey', FakeWebauthn::attestationResponse())
        ->assertHasFormErrors(['name' => 'required']);

    Event::assertNotDispatched(PasskeyRegistered::class);

    $this->assertDatabaseMissing(WebauthnKey::class, [
        'user_id' => $this->user->id,
    ]);
});

it('requires a unique key name', function () {
    WebauthnKey::factory()->notPasskey()->for($this->user)->create(['name' => 'taken name']);

    Webauthn::generateChallengeWith(fn (): string => FakeWebauthn::rawAttestationChallenge());

    // Simulate call to controller.
    storeAttestationPublicKeyInSession($this->user, MfaSession::PasskeyAttestationPk->value);

    livewire(PasskeyManager::class)
        ->set('name', 'taken name')
        ->call('verifyKey', FakeWebauthn::attestationResponse())
        ->assertHasFormErrors(['name' => 'unique']);

    Event::assertNotDispatched(PasskeyRegistered::class);

    $this->assertDatabaseCount(WebauthnKey::class, 1);
});

it('removes its reference to a non-passkey webauthn key if the key is deleted before an upgrade is processed', function () {
    $webauthnKey = WebauthnKey::factory()->upgradeableToPasskey()->for($this->user)->create();

    livewire(PasskeyManager::class)
        ->dispatch(MfaEvent::StartPasskeyUpgrade->value, id: $webauthnKey->id)
        ->assertSet('upgrading.id', $webauthnKey->id)
        ->dispatch(MfaEvent::WebauthnKeyDeleted->value, id: $webauthnKey->id)
        ->assertSet('upgrading', null);
});

it('does nothing to the upgrading key reference if the webauthn key being deleted else where is a different key', function () {
    $webauthnKey = WebauthnKey::factory()->upgradeableToPasskey()->for($this->user)->create();

    livewire(PasskeyManager::class)
        ->dispatch(MfaEvent::StartPasskeyUpgrade->value, id: $webauthnKey->id)
        ->assertSet('upgrading.id', $webauthnKey->id)
        ->dispatch(MfaEvent::WebauthnKeyDeleted->value, id: $webauthnKey->id + 1)
        ->assertSet('upgrading.id', $webauthnKey->id);
});

it("shows a user's passkeys in descending order", function () {
    WebauthnKey::factory()
        ->state(new Sequence(
            ['created_at' => now(), 'name' => 'key--one'],
            ['created_at' => now()->subSecond(), 'name' => 'key--two'],
            ['created_at' => now()->addSecond(), 'name' => 'key--three'],
        ))
        ->passkey()
        ->for($this->user)
        ->count(3)
        ->create();

    livewire(PasskeyManager::class)
        ->assertSeeInOrder([
            'key--three',
            'key--one',
            'key--two',
        ]);
});

it('does not show regular webauthn keys', function () {
    WebauthnKey::factory()->passkey()->for($this->user)->create(['name' => 'is a passkey']);
    WebauthnKey::factory()->notPasskey()->for($this->user)->create(['name' => 'not a passkey']);

    livewire(PasskeyManager::class)
        ->assertSeeText('is a passkey')
        ->assertDontSeeText('not a passkey');
});
