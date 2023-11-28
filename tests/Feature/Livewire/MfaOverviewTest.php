<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\RecoveryCodesViewed;
use Rawilk\ProfileFilament\Livewire\MfaOverview;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Sinnbeck\DomAssertions\Asserts\AssertElement;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Event::fake();

    login($this->user = User::factory()->create());

    Route::get('/_test', fn () => Blade::render('@livewire(\'' . MfaOverview::class . '\')'));

    disableSudoMode();
});

it('can be rendered', function () {
    get('/_test')->assertOk();
});

it('shows the recovery codes in a modal when mfa is first enabled', function (string $event) {
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

    $component
        ->dispatch($event)
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

    test('can be shown', function () {
        livewire(MfaOverview::class)
            ->assertDontSeeText('code-one')
            ->callAction('toggleRecoveryCodes')
            ->assertSet('showRecoveryCodes', true)
            ->assertSeeText('code-one');

        Event::assertDispatched(RecoveryCodesViewed::class);
    });

    it('requires sudo mode to show', function () {
        enableSudoMode();

        livewire(MfaOverview::class)
            ->call('mountAction', 'toggleRecoveryCodes')
            ->assertActionMounted('sudoChallenge')
            ->assertSet('showRecoveryCodes', false)
            ->assertDontSeeText('code-one');

        Event::assertNotDispatched(RecoveryCodesViewed::class);
    });

    it('does not require sudo mode to hide', function () {
        enableSudoMode();

        livewire(MfaOverview::class, ['showRecoveryCodes' => true])
            ->assertSeeText('code-one')
            ->callAction('toggleRecoveryCodes')
            ->assertSet('showRecoveryCodes', false)
            ->assertDontSeeText('code-one')
            ->assertActionNotMounted('sudoChallenge');
    });
});

describe('webauthn', function () {
    beforeEach(function () {
        enableMfa($this->user);

        WebauthnKey::factory()->notPasskey()->for($this->user)->create(['name' => 'my key']);
    });

    it('shows how many keys are registered', function () {
        get('/_test')
            ->assertElementExists('#webauthn-list-container', function (AssertElement $div) {
                $div
                    ->containsText(__('profile-filament::pages/security.mfa.method_configured'))
                    ->containsText('1 key')
                    ->doesntContainText('my key');
            });
    });

    it('does not show a badge if no keys are registered', function () {
        $this->user->webauthnKeys()->delete();

        get('/_test')
            ->assertElementExists('#webauthn-list-container', function (AssertElement $div) {
                $div
                    ->doesntContainText(__('profile-filament::pages/security.mfa.method_configured'))
                    ->doesntContainText('1 key');
            });
    });

    it('does not include passkeys in the registered key count', function () {
        WebauthnKey::factory()->passkey()->for($this->user)->create();

        get('/_test')
            ->assertElementExists('#webauthn-list-container', function (AssertElement $div) {
                $div
                    ->containsText(__('profile-filament::pages/security.mfa.method_configured'))
                    ->containsText('1 key');
            });
    });

    it('can toggle the keys', function () {
        livewire(MfaOverview::class)
            ->assertDontSeeText('my key')
            ->callAction('toggleWebauthn')
            ->assertSuccessful()
            ->assertSet('showWebauthn', true)
            ->assertDispatched(MfaEvent::ToggleWebauthnKeys->value, show: true)
            ->callAction('toggleWebauthn')
            ->assertSet('showWebauthn', false)
            ->assertDispatched(MfaEvent::ToggleWebauthnKeys->value, show: false);
    });

    it('requires sudo mode to show if no keys are registered', function () {
        enableSudoMode();
        $this->user->webauthnKeys()->delete();

        livewire(MfaOverview::class)
            ->call('mountAction', 'toggleWebauthn')
            ->assertActionMounted('sudoChallenge')
            ->assertSet('showWebauthn', false)
            ->assertNotDispatched(MfaEvent::ToggleWebauthnKeys->value);
    });

    it('keeps webauthn shown if another key is still registered when one is deleted', function () {
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

    it('shows how many apps are registered', function () {
        get('/_test')
            ->assertElementExists('#totp-list-container', function (AssertElement $div) {
                $div
                    ->containsText(__('profile-filament::pages/security.mfa.method_configured'))
                    ->containsText('1 app')
                    ->doesntContainText('my app');
            });
    });

    it('does not show a badge if no apps are registered', function () {
        $this->user->authenticatorApps()->delete();

        get('/_test')
            ->assertElementExists('#totp-list-container', function (AssertElement $div) {
                $div
                    ->doesntContainText(__('profile-filament::pages/security.mfa.method_configured'))
                    ->doesntContainText('1 app');
            });
    });

    it('can toggle the totp apps list', function () {
        livewire(MfaOverview::class)
            ->callAction('toggleTotp')
            ->assertSuccessful()
            ->assertSet('showAuthenticatorAppForm', true)
            ->assertDispatched(MfaEvent::ShowAppForm->value)
            ->callAction('toggleTotp')
            ->assertSet('showAuthenticatorAppForm', false)
            ->assertDispatched(MfaEvent::HideAppList->value);
    });

    it('requires sudo mode to show the form when no apps are registered', function () {
        $this->user->authenticatorApps()->delete();
        enableSudoMode();

        livewire(MfaOverview::class)
            ->call('mountAction', 'toggleTotp')
            ->assertActionMounted('sudoChallenge')
            ->assertSet('showAuthenticatorAppForm', false)
            ->assertNotDispatched(MfaEvent::ShowAppForm->value);
    });

    it('does not hide the totp list when at least one app is still registered after an app is deleted', function () {
        livewire(MfaOverview::class, [
            'showAuthenticatorAppForm' => true,
        ])
            ->dispatch(MfaEvent::AppDeleted->value)
            ->assertSet('showAuthenticatorAppForm', true);
    });
});

function disableMfa(User $user): void
{
    $user->update([
        'two_factor_enabled' => false,
        'two_factor_recovery_codes' => null,
    ]);
}

function enableMfa(User $user): void
{
    $user->update([
        'two_factor_enabled' => true,
        'two_factor_recovery_codes' => Crypt::encryptString(
            json_encode([
                'code-one',
                'code-two',
                'code-three',
                'code-four',
            ])
        ),
    ]);
}
