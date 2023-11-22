<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Event;
use PragmaRX\Google2FA\Google2FA;
use Rawilk\ProfileFilament\Actions\AuthenticatorApps\ConfirmTwoFactorAppAction;
use Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction;
use Rawilk\ProfileFilament\Contracts\AuthenticatorAppService as AuthenticatorAppServiceContract;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppAdded;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasEnabled;
use Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\AuthenticatorAppForm;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Services\AuthenticatorAppService;
use Rawilk\ProfileFilament\Tests\Fixtures\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    config([
        'profile-filament.actions.confirm_authenticator_app' => ConfirmTwoFactorAppAction::class,
        'profile-filament.actions.mark_two_factor_enabled' => MarkTwoFactorEnabledAction::class,
    ]);

    disableSudoMode();
});

it('does not show anything by default', function () {
    livewire(AuthenticatorAppForm::class)
        ->assertSuccessful()
        ->assertDontSee('#authenticator-apps-list')
        ->assertDontSeeHtml('<form');
});

it('can register a new authenticator app for a user', function () {
    app()->bind(AuthenticatorAppServiceContract::class, MockAuthenticatorAppService::class);

    Event::fake();
    actingAs($user = User::factory()->withoutMfa()->create());

    $mfaEngine = app(Google2FA::class);
    $userSecret = $mfaEngine->generateSecretKey();
    $validOtp = $mfaEngine->getCurrentOtp($userSecret);

    MockAuthenticatorAppService::$secret = $userSecret;

    livewire(AuthenticatorAppForm::class, [
        'authenticatorApps' => collect(),
    ])
        // Since our apps list is empty, the component should also call the "showAddForm" method
        ->call('showApps')
        ->assertSuccessful()
        ->assertSeeHtml('<form')
        ->assertSet('secret', $userSecret)
        ->set('name', 'My app')
        ->set('code', $validOtp)
        ->call('confirm')
        ->assertSuccessful()
        ->assertDispatched(MfaEvent::AppAdded->value, enabledMfa: true)
        ->assertSet('secret', '');

    Event::assertDispatched(TwoFactorAppAdded::class);
    Event::assertDispatched(TwoFactorAuthenticationWasEnabled::class);

    $user->refresh();
    $authenticatorApp = $user->authenticatorApps()->first();

    expect($user->two_factor_enabled)->toBeTrue()
        ->and($user->recoveryCodes())->toHaveCount(8)
        ->and($authenticatorApp)
        ->toBeInstanceOf(AuthenticatorApp::class)
        ->name->toBe('My app')
        ->last_used_at->toBeNull();
});

test('a valid otp code is required to register an authenticator app', function () {
    app()->bind(AuthenticatorAppServiceContract::class, MockAuthenticatorAppService::class);

    Event::fake();
    actingAs($user = User::factory()->withoutMfa()->create());

    $mfaEngine = app(Google2FA::class);
    $userSecret = $mfaEngine->generateSecretKey();

    MockAuthenticatorAppService::$secret = $userSecret;

    livewire(AuthenticatorAppForm::class, [
        'authenticatorApps' => collect(),
    ])
        // Since our apps list is empty, the component should also call the "showAddForm" method
        ->call('showApps')
        ->set('name', 'My app')
        ->set('code', 'invalid')
        ->call('confirm')
        ->assertSet('codeValid', false)
        ->assertNotDispatched(MfaEvent::AppAdded->value);

    Event::assertNotDispatched(TwoFactorAppAdded::class);
    Event::assertNotDispatched(TwoFactorAuthenticationWasEnabled::class);

    $user->refresh();

    expect($user->two_factor_enabled)->toBeFalse()
        ->and($user->authenticatorApps()->count())->toBe(0);
});

test('sudo mode is required to register an app', function () {
    enableSudoMode();

    app()->bind(AuthenticatorAppServiceContract::class, MockAuthenticatorAppService::class);

    Event::fake();
    actingAs($user = User::factory()->withoutMfa()->create());

    $mfaEngine = app(Google2FA::class);
    $userSecret = $mfaEngine->generateSecretKey();
    $validOtp = $mfaEngine->getCurrentOtp($userSecret);

    MockAuthenticatorAppService::$secret = $userSecret;

    livewire(AuthenticatorAppForm::class, [
        'authenticatorApps' => collect(),
    ])
        ->call('showApps')
        ->set('name', 'My app')
        ->set('code', $validOtp)
        ->call('confirm')
        ->assertActionMounted('sudoChallenge')
        ->assertNotDispatched(MfaEvent::AppAdded->value);

    $user->refresh();

    Event::assertNotDispatched(TwoFactorAuthenticationWasEnabled::class);

    expect($user->two_factor_enabled)->toBeFalse();
});

it("shows a user's registered authenticators in descending order", function () {
    actingAs($user = User::factory()->withMfa()->create());

    $apps = AuthenticatorApp::factory()
        ->state(new Sequence(
            ['created_at' => now(), 'name' => 'app--One'],
            ['created_at' => now()->subSecond(), 'name' => 'app--Two'],
            ['created_at' => now()->addSecond(), 'name' => 'app--Three'],
        ))
        ->for($user)
        ->count(3)
        ->create();

    livewire(AuthenticatorAppForm::class, [
        'authenticatorApps' => $apps,
    ])
        ->call('showApps')
        ->assertSeeInOrder([
            'app--Three',
            'app--One',
            'app--Two',
        ])
        ->assertSet('showForm', false);
});

it('requires a unique name for the authenticator app', function () {
    app()->bind(AuthenticatorAppServiceContract::class, MockAuthenticatorAppService::class);

    actingAs($user = User::factory()->withoutMfa()->create());

    AuthenticatorApp::factory()->for($user)->create(['name' => 'my app']);

    $mfaEngine = app(Google2FA::class);
    $userSecret = $mfaEngine->generateSecretKey();
    $validOtp = $mfaEngine->getCurrentOtp($userSecret);

    MockAuthenticatorAppService::$secret = $userSecret;

    livewire(AuthenticatorAppForm::class, [
        'authenticatorApps' => $user->authenticatorApps,
    ])
        ->call('showApps')
        ->call('showAddForm')
        ->set('name', 'my app')
        ->set('code', $validOtp)
        ->call('confirm')
        ->assertHasFormErrors([
            'name' => 'unique',
        ])
        ->assertNotDispatched(MfaEvent::AppAdded->value);
});

class MockAuthenticatorAppService extends AuthenticatorAppService
{
    public static $secret;

    public function generateSecretKey(): string
    {
        return static::$secret;
    }
}
