<?php

declare(strict_types=1);

use PragmaRX\Google2FA\Google2FA;
use Rawilk\ProfileFilament\Actions\AuthenticatorApps\ConfirmTwoFactorAppAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Contracts\AuthenticatorAppService as AuthenticatorAppServiceContract;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\AuthenticatorAppForm;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;
use Rawilk\ProfileFilament\Tests\Fixtures\Support\MockAuthenticatorAppService;

use function Pest\Livewire\livewire;

beforeEach(function () {
    config([
        'profile-filament.actions.confirm_authenticator_app' => ConfirmTwoFactorAppAction::class,
        'profile-filament.actions.mark_two_factor_enabled' => MarkTwoFactorEnabledAction::class,
    ]);

    $this->app->bind(AuthenticatorAppServiceContract::class, MockAuthenticatorAppService::class);

    Event::fake();

    login($this->user = User::factory()->withoutMfa()->create());

    disableSudoMode();

    $this->mfaEngine = app(Google2FA::class);
    $this->userSecret = $this->mfaEngine->generateSecretKey();
});

it('does not show anything initially', function () {
    livewire(AuthenticatorAppForm::class)
        ->assertSuccessful()
        ->assertDontSee('totp-list-container')
        ->assertDontSeeHtml('<form');
});

it('can register a new authenticator app for a user', function () {
    $validOtp = $this->mfaEngine->getCurrentOtp($this->userSecret);

    MockAuthenticatorAppService::$secret = $this->userSecret;

    // We already know this action works from other tests, so there's
    // no need to test the outcome of it again.
    $this->mock(ConfirmTwoFactorAppAction::class)
        ->shouldReceive('__invoke')
        ->with(
            $this->user,
            'My app',
            $this->userSecret,
        )
        ->once();

    livewire(AuthenticatorAppForm::class, [
        'authenticatorApps' => collect(),
    ])
        ->call('showApps')
        // Since the app list is empty, the component should also call the "showAddForm" method
        ->assertSuccessful()
        ->assertSeeHtml('<form')
        ->assertSeeHtml('<svg')
        ->assertSet('secret', $this->userSecret)
        ->set('name', 'My app')
        ->set('code', $validOtp)
        ->call('confirm')
        ->assertHasNoErrors()
        ->assertDispatched(MfaEvent::AppAdded->value, enabledMfa: true)
        ->assertSet('secret', '');
});

test('a valid otp code is required to register an authenticator app', function () {
    MockAuthenticatorAppService::$secret = $this->userSecret;

    $this->mock(ConfirmTwoFactorAppAction::class)
        ->shouldNotReceive('__invoke');

    livewire(AuthenticatorAppForm::class, [
        'authenticatorApps' => collect(),
    ])
        ->call('showApps')
        ->set('name', 'My app')
        ->set('code', 'invalid')
        ->call('confirm')
        ->assertSet('codeValid', false)
        ->assertNotDispatched(MfaEvent::AppAdded->value);
});

test('sudo mode can be required to register an app', function () {
    enableSudoMode();

    $validOtp = $this->mfaEngine->getCurrentOtp($this->userSecret);

    MockAuthenticatorAppService::$secret = $this->userSecret;

    $this->mock(ConfirmTwoFactorAppAction::class)
        ->shouldNotReceive('__invoke');

    livewire(AuthenticatorAppForm::class, [
        'authenticatorApps' => collect(),
    ])
        ->call('showApps')
        ->set('name', 'My app')
        ->set('code', $validOtp)
        ->call('confirm')
        ->assertHasNoErrors()
        ->assertSet('codeValid', true)
        ->assertNotDispatched(MfaEvent::AppAdded->value);
});

it('shows a users registered apps in descending (registration) order', function () {
    $this->freezeSecond();

    login($user = User::factory()->withMfa()->create());

    $apps = AuthenticatorApp::factory()
        ->sequence(
            ['created_at' => now(), 'name' => 'app.one'],
            ['created_at' => now()->subSecond(), 'name' => 'app.two'],
            ['created_at' => now()->addSecond(), 'name' => 'app.three'],
        )
        ->for($user)
        ->count(3)
        ->create();

    livewire(AuthenticatorAppForm::class, [
        'authenticatorApps' => $apps,
    ])
        ->call('showApps')
        ->assertSeeTextInOrder([
            'app.three',
            'app.one',
            'app.two',
        ])
        ->assertSet('showForm', false);
});

it('requires a unique name for each app', function () {
    AuthenticatorApp::factory()->for($this->user)->create(['name' => 'My app']);

    $validOtp = $this->mfaEngine->getCurrentOtp($this->userSecret);

    MockAuthenticatorAppService::$secret = $this->userSecret;

    $this->mock(ConfirmTwoFactorAppAction::class)
        ->shouldNotReceive('__invoke');

    livewire(AuthenticatorAppForm::class, [
        'authenticatorApps' => $this->user->authenticatorApps,
    ])
        ->call('showApps')
        ->call('showAddForm')
        ->set('name', 'My app')
        ->set('code', $validOtp)
        ->call('confirm')
        ->assertHasFormErrors([
            'name' => ['unique'],
        ])
        ->assertNotDispatched(MfaEvent::AppAdded->value);
});
