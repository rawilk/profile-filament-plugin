<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Rawilk\ProfileFilament\Auth\Multifactor\Livewire\MultiFactorAuthenticationManager;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\RecoveryCodeProvider;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\Tests\TestSupport\Models\User;
use Valorin\Random\Random;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    actingAs(User::factory()->create());

    disableSudoMode();

    $this->component = MultiFactorAuthenticationManager::class;
    $this->schema = 'content.data';
    $this->actionName = 'setUpAuthenticatorApp';
});

describe('setup flow', function () {
    it('can generate a secret when the action is mounted', function () {
        livewire($this->component)
            ->mountAction(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema))
            ->assertActionMounted(
                TestAction::make($this->actionName)
                    ->schemaComponent('app', schema: $this->schema)
                    ->arguments(function (array $actualArguments): bool {
                        $encrypted = Crypt::decrypt($actualArguments['encrypted']);

                        if (blank($encrypted['secret'] ?? null)) {
                            return false;
                        }

                        if (blank($encrypted['userId'] ?? null)) {
                            return false;
                        }

                        return $encrypted['userId'] === auth()->id();
                    })
            );
    });

    it('can require sudo mode to mount', function () {
        enableSudoMode();

        livewire($this->component)
            ->mountAction(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema))
            ->assertActionMounted('sudoChallenge')
            ->assertActionNotMounted(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema));
    });

    it('saves the authenticator app secret and generates recovery codes when the action is submitted', function () {
        getPlugin()->multiFactorRecovery(provider: RecoveryCodeProvider::make()->generateCodesUsing(function () {
            return Random::dashed();
        }));

        $appAuthenticationProvider = getPlugin()->getMultiFactorAuthenticationProvider('app');

        $user = auth()->user();

        expect($user->hasMultiFactorAuthenticationEnabled())->toBeFalse();

        $livewire = livewire($this->component)
            ->mountAction(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema));

        $encryptedActionArguments = Crypt::decrypt($livewire->instance()->mountedActions[0]['arguments']['encrypted']);
        $secret = $encryptedActionArguments['secret'];

        $livewire
            ->fillForm(['code' => $appAuthenticationProvider->getCurrentCode($secret), 'name' => 'My app'])
            ->callMountedAction()
            ->assertHasNoFormErrors()

            // Once the form is submitted, recovery codes should be generated and shown since we have recovery enabled
            // on the test admin panel.
            ->assertActionMounted(
                TestAction::make('showRecoveryCodes')
                    ->arguments(function (array $actualArguments) use ($user) {
                        $encrypted = Crypt::decrypt($actualArguments['encrypted']);
                        $rawRecoveryCodes = $encrypted['recoveryCodes'];

                        $recoveryCodes = $user->getAuthenticationRecoveryCodes();

                        expect($recoveryCodes)->toBeArray()
                            ->toHaveCount(8);

                        foreach ($recoveryCodes as $hashedRecoveryCode) {
                            expect(Hash::check(array_shift($rawRecoveryCodes), $hashedRecoveryCode))
                                ->toBeTrue();
                        }

                        return true;
                    })
            );

        expect($user->getPreferredMfaProvider())->toBe($appAuthenticationProvider->getId());

        assertDatabaseHas(Rawilk\ProfileFilament\Support\AuthenticatorApp::class, [
            'user_id' => $user->getKey(),
            'name' => 'My app',
        ]);

        expect($user->hasMultiFactorAuthenticationEnabled())->toBeTrue();

        $record = $user->authenticatorApps()->first();
        expect($record)->secret->toBe($secret);
    });

    it('requires a valid code', function () {
        /** @var Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider $appAuthenticationProvider */
        $appAuthenticationProvider = getPlugin()->getMultiFactorAuthenticationProvider('app');

        /** @var Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication $user */
        $user = auth()->user();

        expect($user->hasMultiFactorAuthenticationEnabled())->toBeFalse();

        $livewire = livewire($this->component)
            ->mountAction(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema));

        $encryptedActionArguments = Crypt::decrypt($livewire->instance()->mountedActions[0]['arguments']['encrypted']);
        $secret = $encryptedActionArguments['secret'];

        $livewire->fillForm([
            'code' => ($appAuthenticationProvider->getCurrentCode($secret) === '000000') ? '111111' : '000000',
            'name' => 'My app',
        ])
            ->callMountedAction()
            ->assertHasFormErrors();

        expect($user->hasMultiFactorAuthenticationEnabled())->toBeFalse();

        assertDatabaseMissing(AuthenticatorApp::class);
    });

    it('does not show recovery codes if they are not enabled on the panel', function () {
        getPlugin()->multiFactorRecovery(null);

        $appAuthenticationProvider = getPlugin()->getMultiFactorAuthenticationProvider('app');

        $user = auth()->user();

        $livewire = livewire($this->component)
            ->mountAction(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema));

        $encryptedActionArguments = Crypt::decrypt($livewire->instance()->mountedActions[0]['arguments']['encrypted']);
        $secret = $encryptedActionArguments['secret'];

        $livewire
            ->fillForm(['code' => $appAuthenticationProvider->getCurrentCode($secret), 'name' => 'My app'])
            ->callMountedAction()
            ->assertHasNoFormErrors()
            ->assertActionNotMounted(TestAction::make('showRecoveryCodes'));

        expect($user->getAuthenticationRecoveryCodes())->toBeNull()
            ->and($user->hasMultiFactorAuthenticationEnabled())->toBeTrue();
    });
});

describe('validation', function () {
    test('code is required', function () {
        $user = auth()->user();

        livewire($this->component)
            ->mountAction(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema))
            ->fillForm(['code' => ''])
            ->callMountedAction()
            ->assertHasFormErrors([
                'code' => 'required',
            ]);

        assertDatabaseMissing(AuthenticatorApp::class);

        expect($user->hasMultiFactorAuthenticationEnabled())->toBeFalse()
            ->and($user->getAuthenticationRecoveryCodes())->toBeNull();
    });

    test('code must be 6 digits', function () {
        /** @var Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider $appAuthenticationProvider */
        $appAuthenticationProvider = getPlugin()->getMultiFactorAuthenticationProvider('app');

        /** @var Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication $user */
        $user = auth()->user();

        $livewire = livewire($this->component)
            ->mountAction(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema));

        $encryptedActionArguments = Crypt::decrypt($livewire->instance()->mountedActions[0]['arguments']['encrypted']);
        $secret = $encryptedActionArguments['secret'];

        $livewire->fillForm([
            'code' => Str::limit($appAuthenticationProvider->getCurrentCode($secret), limit: 5, end: ''),
            'name' => 'My app',
        ])
            ->callMountedAction()
            ->assertHasFormErrors([
                'code' => 'digits',
            ]);

        expect($user->hasMultiFactorAuthenticationEnabled())->toBeFalse();

        assertDatabaseMissing(AuthenticatorApp::class);
    });

    test('name is required', function () {
        /** @var Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider $appAuthenticationProvider */
        $appAuthenticationProvider = getPlugin()->getMultiFactorAuthenticationProvider('app');

        /** @var Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication $user */
        $user = auth()->user();

        $livewire = livewire($this->component)
            ->mountAction(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema));

        $encryptedActionArguments = Crypt::decrypt($livewire->instance()->mountedActions[0]['arguments']['encrypted']);
        $secret = $encryptedActionArguments['secret'];

        $livewire->fillForm([
            'code' => $appAuthenticationProvider->getCurrentCode($secret),
            'name' => '',
        ])
            ->callMountedAction()
            ->assertHasFormErrors([
                'name' => 'required',
            ]);

        expect($user->hasMultiFactorAuthenticationEnabled())->toBeFalse();

        assertDatabaseMissing(AuthenticatorApp::class);
    });

    test('name must be unique to the user', function () {
        /** @var Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider $appAuthenticationProvider */
        $appAuthenticationProvider = getPlugin()->getMultiFactorAuthenticationProvider('app');

        /** @var Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication $user */
        $user = auth()->user();

        AuthenticatorApp::factory()->for($user)->create(['name' => 'My app']);

        $livewire = livewire($this->component)
            ->mountAction(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema));

        $encryptedActionArguments = Crypt::decrypt($livewire->instance()->mountedActions[0]['arguments']['encrypted']);
        $secret = $encryptedActionArguments['secret'];

        $livewire->fillForm([
            'code' => Str::limit($appAuthenticationProvider->getCurrentCode($secret), limit: 5, end: ''),
            'name' => 'My app',
        ])
            ->callMountedAction()
            ->assertHasFormErrors([
                'name' => 'unique',
            ]);

        assertDatabaseCount(AuthenticatorApp::class, 1);
    });
});

it('can throttle code verification attempts per user', function () {
    /** @var Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider $appAuthenticationProvider */
    $appAuthenticationProvider = getPlugin()->getMultiFactorAuthenticationProvider('app');

    $user = auth()->user();

    // Pre-fill the rate limiter to simulate prior attempts.
    $rateLimitKey = 'pf-set-up-app-authentication:' . $user->getKey();

    foreach (range(1, 5) as $i) {
        RateLimiter::hit($rateLimitKey);
    }

    $livewire = livewire($this->component)
        ->mountAction(TestAction::make($this->actionName)->schemaComponent('app', schema: $this->schema));

    $encryptedActionArguments = Crypt::decrypt($livewire->instance()->mountedActions[0]['arguments']['encrypted']);
    $secret = $encryptedActionArguments['secret'];

    // Even with a valid code, the rate limiter should block the attempt.
    $livewire
        ->fillForm(['code' => $appAuthenticationProvider->getCurrentCode($secret), 'name' => 'My app'])
        ->callMountedAction()
        ->assertHasFormErrors(['code']);

    assertDatabaseMissing(AuthenticatorApp::class);
});
