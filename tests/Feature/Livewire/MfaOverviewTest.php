<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\RecoveryCodesViewed;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\ToggleRecoveryCodesAction;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\ToggleTotpFormAction;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\ToggleWebauthnAction;
use Rawilk\ProfileFilament\Livewire\MfaOverview;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

// use Sinnbeck\DomAssertions\Asserts\AssertElement;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    login($this->user = User::factory()->create());

    disableSudoMode();

    Route::get('/_test', fn () => Blade::render('@livewire("' . MfaOverview::class . '")'));
});

it('renders', function () {
    livewire(MfaOverview::class)
        ->assertOk();
});

it('shows recovery codes in a modal when mfa is first enabled', function (string $event) {
    livewire(MfaOverview::class)
        ->dispatch($event, enabledMfa: true)
        ->assertSet('showRecoveryInModal', true)
        ->assertDispatched('open-modal', id: 'mfa-recovery-codes');
})->with([
    'totp' => MfaEvent::AppAdded->value,
    'webauthn' => MfaEvent::WebauthnKeyAdded->value,
    'passkey' => MfaEvent::PasskeyRegistered->value,
]);

it('hides everything when mfa is disabled', function (string $event) {
    $component = livewire(MfaOverview::class, [
        'showAuthenticatorAppForm' => true,
        'showWebauthn' => true,
        'showRecoveryInModal' => true,
    ]);

    disableMfa($this->user);

    $component->dispatch($event)
        ->assertSet('showAuthenticatorAppForm', false)
        ->assertSet('showWebauthn', false)
        ->assertSet('showRecoveryInModal', false)
        ->assertSet('showRecoveryCodes', false);
})->with([
    'totp' => MfaEvent::AppDeleted->value,
    'webauthn' => MfaEvent::WebauthnKeyDeleted->value,
    'webauthn upgraded' => MfaEvent::WebauthnKeyUpgradedToPasskey->value,
]);

describe('recovery codes', function () {
    beforeEach(function () {
        enableMfa($this->user);
    });

    it('can show the recovery codes', function () {
        livewire(MfaOverview::class)
            ->assertDontSeeText('code-one')
            ->callAction(ToggleRecoveryCodesAction::class)
            ->assertSet('showRecoveryCodes', true)
            ->assertSeeText('code-one');

        Event::assertDispatched(RecoveryCodesViewed::class);
    });

    it('can require sudo mode to show', function () {
        enableSudoMode();

        livewire(MfaOverview::class)
            ->call('mountAction', 'toggleRecoveryCodes')
            ->assertActionNotMounted(ToggleRecoveryCodesAction::class)
            ->assertSeeText(sudoChallengeTitle())
            ->assertDontSeeText('code-one');

        Event::assertNotDispatched(RecoveryCodesViewed::class);
    });

    it('does not require sudo mode to hide the codes', function () {
        enableSudoMode();

        livewire(MfaOverview::class, ['showRecoveryCodes' => true])
            ->assertSeeText('code-one')
            ->callAction(ToggleRecoveryCodesAction::class)
            ->assertSet('showRecoveryCodes', false)
            ->assertDontSeeText('code-one')
            ->assertDontSeeText(__('profile-filament::messages.sudo_challenge.title'));
    });
});

describe('webauthn', function () {
    beforeEach(function () {
        enableMfa($this->user);

        WebauthnKey::factory()->notPasskey()->for($this->user)->create(['name' => 'my key']);
    });

    it('shows how many keys a user has registered', function () {
    get('/_test')
    ->assertElementExists('[data-test="webauthn-container"]', function (AssertElement $div) {
        $div
            ->containsText(__('profile-filament::pages/security.mfa.method_configured'))
            ->containsText('1 key')
            // keys are not shown initially
            ->doesntContainText('my key');
    });
        })->skip('Skip until dom assertions dep is updated.');

    it('does not show a badge if keys are not registered', function () {
        $this->user->webauthnKeys()->delete();

        get('/_test')
            ->assertElementExists('[data-test="webauthn-container"]', function (AssertElement $div) {
                $div
                    ->doesntContainText(__('profile-filament::pages/security.mfa.method_configured'))
                    ->doesntContainText('1 key');
            });
    })->skip('Skip until dom assertions dep is updated.');

    it('does not include passkeys in the registered key count', function () {
        WebauthnKey::factory()->passkey()->for($this->user)->create();

        get('/_test')
            ->assertElementExists('[data-test="webauthn-container"]', function (AssertElement $div) {
                $div
                    ->containsText(__('profile-filament::pages/security.mfa.method_configured'))
                    ->containsText('1 key');
            });
    })->skip('Skip until dom assertions dep is updated.');

    it('can show and hide the registered keys', function () {
        livewire(MfaOverview::class)
            ->assertDontSeeText('my key')
            ->callAction(ToggleWebauthnAction::class)
            ->assertSet('showWebauthn', true)
            ->assertDispatched(MfaEvent::ToggleWebauthnKeys->value, show: true)
            ->callAction(ToggleWebauthnAction::class)
            ->assertSet('showWebauthn', false)
            ->assertDispatched(MfaEvent::ToggleWebauthnKeys->value, show: false);
    });

    // Sudo mode is required for this because the register key action is shown initially
    // in this case, and the action is a sensitive action.
    it('can require sudo mode to show if no keys are registered', function () {
        enableSudoMode();
        $this->user->webauthnKeys()->delete();

        livewire(MfaOverview::class)
            ->call('mountAction', 'toggleWebauthn')
            ->assertSet('showWebauthn', false)
            ->assertNotDispatched(MfaEvent::ToggleWebauthnKeys->value)
            ->assertSeeText(sudoChallengeTitle());
    });

    it('keeps the registered key list open if at least one key is still registered when another one is deleted', function () {
        livewire(MfaOverview::class, [
            'showWebauthn' => true,
        ])
            ->dispatch(MfaEvent::WebauthnKeyDeleted->value)
            ->assertSet('showWebauthn', true);
    });
});

describe('totp', function () {
    beforeEach(function () {
        enableMfa($this->user);

        AuthenticatorApp::factory()->for($this->user)->create(['name' => 'my app']);
    });

    it('shows how many apps are registered for a user', function () {
    get('/_test')
    ->assertElementExists('[data-test="totp-container"]', function (AssertElement $div) {
        $div
            ->containsText(__('profile-filament::pages/security.mfa.method_configured'))
            ->containsText('1 app')
            ->doesntContainText('my app');
    });
        })->skip('Skip until dom assertions dep is updated.');

    it('does not show a badge if no apps are registered', function () {
        $this->user->authenticatorApps()->delete();

        get('/_test')
            ->assertElementExists('[data-test="totp-container"]', function (AssertElement $div) {
                $div
                    ->doesntContainText(__('profile-filament::pages/security.mfa.method_configured'))
                    ->doesntContainText('1 app');
            });
    })->skip('Skip until dom assertions dep is updated.');

    it('can toggle the totp apps list', function () {
        livewire(MfaOverview::class)
            ->callAction(ToggleTotpFormAction::class)
            ->assertSet('showAuthenticatorAppForm', true)
            ->assertDispatched(MfaEvent::ShowAppForm->value)
            ->callAction(ToggleTotpFormAction::class)
            ->assertSet('showAuthenticatorAppForm', false)
            ->assertDispatched(MfaEvent::HideAppList->value);
    });

    // Sudo mode is required for this because the register app action is shown initially
    // in this case, and the action is a sensitive action.
    it('can require sudo mode to show the form when no apps are registered to the user', function () {
        $this->user->authenticatorApps()->delete();
        enableSudoMode();

        livewire(MfaOverview::class)
            ->call('mountAction', 'toggleTotp')
            ->assertSet('showAuthenticatorAppForm', false)
            ->assertNotDispatched(MfaEvent::ShowAppForm->value)
            ->assertSeeText(sudoChallengeTitle());
    });

    it('does not hide the totp list when at least one app is still registered after an app is deleted', function () {
        livewire(MfaOverview::class, [
            'showAuthenticatorAppForm' => true,
        ])
            ->dispatch(MfaEvent::AppDeleted->value)
            ->assertSet('showAuthenticatorAppForm', true);
    });
});
