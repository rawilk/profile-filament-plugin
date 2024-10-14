<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Livewire\PasskeyManager;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();
    Route::webauthn();

    disableSudoMode();

    login($this->user = User::factory()->withoutMfa()->create());
});

it('renders', function () {
    livewire(PasskeyManager::class)
        ->assertSuccessful()
        ->assertSeeText(__('profile-filament::pages/security.passkeys.empty_heading'));
});

it('has an action to register a new passkey', function () {
    livewire(PasskeyManager::class)
        ->mountAction('add')
        ->assertSeeText(__('profile-filament::pages/security.passkeys.actions.add.modal_title'))
        ->assertDontSeeText(__('profile-filament::pages/security.passkeys.actions.upgrade.cancel_upgrade'));
});

it('can show the upgrade to passkey form', function () {
    $record = WebauthnKey::factory()->upgradeableToPasskey()->for($this->user)->create();

    livewire(PasskeyManager::class)
        ->dispatch(MfaEvent::StartPasskeyUpgrade->value, id: $record->getKey())
        ->assertActionMounted('add')
        ->assertSet('idToUpgrade', $record->getKey())
        ->assertSeeText(__('profile-filament::pages/security.passkeys.actions.upgrade.cancel_upgrade'));
});

it('can require sudo mode to show the passkey registration form', function () {
    enableSudoMode();

    livewire(PasskeyManager::class)
        ->call('mountAction', 'add')
        ->assertSeeText(sudoChallengeTitle());
});

it('resets the idToUpgrade property when a webauthn key is deleted or upgraded', function (string $event) {
    livewire(PasskeyManager::class, [
        'idToUpgrade' => 1,
    ])
        ->assertSet('idToUpgrade', 1)
        ->dispatch($event)
        ->assertSet('idToUpgrade', null);
})->with([
    MfaEvent::WebauthnKeyDeleted->value,
    MfaEvent::WebauthnKeyUpgradedToPasskey->value,
]);

it('lists a users passkeys in descending (by registration date) order', function () {
    $this->freezeSecond();

    WebauthnKey::factory()
        ->sequence(
            ['created_at' => now(), 'name' => 'key.one'],
            ['created_at' => now()->subSecond(), 'name' => 'key.two'],
            ['created_at' => now()->addSecond(), 'name' => 'key.three'],
        )
        ->passkey()
        ->for($this->user)
        ->count(3)
        ->create();

    livewire(PasskeyManager::class)
        ->assertSeeTextInOrder([
            'key.three',
            'key.one',
            'key.two',
        ]);
});

it('does not show regular webauthn keys', function () {
    $passkey = WebauthnKey::factory()->passkey()->for($this->user)->create();
    $nonPasskey = WebauthnKey::factory()->notPasskey()->for($this->user)->create();

    livewire(PasskeyManager::class)
        ->assertSee("passkeys.{$passkey->getKey()}")
        ->assertDontSee("passkeys.{$nonPasskey->getKey()}");
});

it('resets idToUpgrade property when the add action is clicked normally (not from an upgrade action)', function () {
    livewire(PasskeyManager::class, [
        'idToUpgrade' => 1,
    ])
        ->mountAction('add')
        ->assertSet('idToUpgrade', null);
});

it('resets its state when a passkey is registered', function () {
    livewire(PasskeyManager::class, [
        'idToUpgrade' => 1,
    ])
        ->dispatch(MfaEvent::PasskeyRegistered->value)
        ->assertSet('idToUpgrade', null)
        ->assertSet('upgrading', null)
        ->assertActionNotMounted('add');
});
