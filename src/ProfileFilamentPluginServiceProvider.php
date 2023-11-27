<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Psr\Log\NullLogger;
use Rawilk\ProfileFilament\Filament\Pages\MfaChallenge;
use Rawilk\ProfileFilament\Filament\Pages\SudoChallenge;
use Rawilk\ProfileFilament\Http\Controllers\PasskeysController;
use Rawilk\ProfileFilament\Http\Controllers\WebauthnPublicKeysController;
use Rawilk\ProfileFilament\Livewire as PackageLivewire;
use Rawilk\ProfileFilament\Responses\EmailRevertedResponse;
use Rawilk\ProfileFilament\Responses\PendingEmailVerifiedResponse;
use Rawilk\ProfileFilament\Services\Mfa;
use Rawilk\ProfileFilament\Services\Sudo;
use Rawilk\ProfileFilament\Services\Webauthn;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class ProfileFilamentPluginServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('profile-filament')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasRoute('web')
            ->hasMigrations([
                'add_two_factor_to_users_table',
                'create_authenticator_apps_table',
                'create_webauthn_keys_table',
                'create_pending_user_emails_table',
            ]);
    }

    public function packageBooted(): void
    {
        $this->makeClassBindings();
        $this->registerRouteMacros();
        $this->registerAssets();
    }

    public function packageRegistered(): void
    {
        $this->registerLivewireComponents();

        $this->app->scoped(
            Contracts\AuthenticatorAppService::class,
            fn ($app) => new Services\AuthenticatorAppService(
                engine: $app->make(Google2FA::class),
                cache: $app->make(Cache::class),
            ),
        );

        $this->app->scoped(
            Mfa::class,
            fn ($app) => new Mfa(userModel: $app['config']['auth.providers.users.model']),
        );

        $this->app->scoped(
            Webauthn::class,
            fn ($app) => new Webauthn(
                model: $app['config']['profile-filament.models.webauthn_key'],
                logger: $app['config']['profile-filament.webauthn.logging_enabled'] === true ? $app['log'] : new NullLogger,
            ),
        );

        $this->app->scoped(
            Sudo::class,
            fn ($app) => new Sudo(
                expiration: $app['config']['profile-filament.sudo.expires'],
            ),
        );
    }

    private function makeClassBindings(): void
    {
        $this->app->bind(Contracts\UpdatePasswordAction::class, fn ($app) => $app->make(config('profile-filament.actions.update_password')));
        $this->app->bind(Contracts\DeleteAccountAction::class, fn ($app) => $app->make(config('profile-filament.actions.delete_account')));

        // General two factor
        $this->app->bind(Contracts\TwoFactor\DisableTwoFactorAction::class, fn ($app) => $app->make(config('profile-filament.actions.disable_two_factor')));
        $this->app->bind(Contracts\TwoFactor\GenerateNewRecoveryCodesAction::class, fn ($app) => $app->make(config('profile-filament.actions.generate_new_recovery_codes')));
        $this->app->bind(Contracts\TwoFactor\MarkTwoFactorDisabledAction::class, fn ($app) => $app->make(config('profile-filament.actions.mark_two_factor_disabled')));
        $this->app->bind(Contracts\TwoFactor\MarkTwoFactorEnabledAction::class, fn ($app) => $app->make(config('profile-filament.actions.mark_two_factor_enabled')));

        // Authenticator apps
        $this->app->bind(Contracts\AuthenticatorApps\ConfirmTwoFactorAppAction::class, fn ($app) => $app->make(config('profile-filament.actions.confirm_authenticator_app')));
        $this->app->bind(Contracts\AuthenticatorApps\DeleteAuthenticatorAppAction::class, fn ($app) => $app->make(config('profile-filament.actions.delete_authenticator_app')));

        // Webauthn
        $this->app->bind(Contracts\Webauthn\DeleteWebauthnKeyAction::class, fn ($app) => $app->make(config('profile-filament.actions.delete_webauthn_key')));
        $this->app->bind(Contracts\Webauthn\RegisterWebauthnKeyAction::class, fn ($app) => $app->make(config('profile-filament.actions.register_webauthn_key')));

        // Passkeys
        $this->app->bind(Contracts\Passkeys\DeletePasskeyAction::class, fn ($app) => $app->make(config('profile-filament.actions.delete_passkey')));
        $this->app->bind(Contracts\Passkeys\RegisterPasskeyAction::class, fn ($app) => $app->make(config('profile-filament.actions.register_passkey')));
        $this->app->bind(Contracts\Passkeys\UpgradeToPasskeyAction::class, fn ($app) => $app->make(config('profile-filament.actions.upgrade_to_passkey')));

        // Pending user emails
        $this->app->bind(Contracts\PendingUserEmail\StoreOldUserEmailAction::class, fn ($app) => $app->make(config('profile-filament.actions.store_old_user_email')));
        $this->app->bind(Contracts\PendingUserEmail\UpdateUserEmailAction::class, fn ($app) => $app->make(config('profile-filament.actions.update_user_email')));

        // Responses
        $this->app->bind(Contracts\Responses\PendingEmailVerifiedResponse::class, PendingEmailVerifiedResponse::class);
        $this->app->bind(Contracts\Responses\EmailRevertedResponse::class, EmailRevertedResponse::class);
    }

    private function registerAssets(): void
    {
        FilamentAsset::register([
            AlpineComponent::make('webauthnForm', __DIR__ . '/../resources/dist/webauthn.js')
                ->loadedOnRequest(),
        ], 'rawilk/profile-filament-plugin');

        $sets = $this->app['config']->get('blade-icons.sets');
        $sets['profile-filament'] = [
            'path' => 'vendor/rawilk/profile-filament-plugin/resources/svg',
            'prefix' => 'pf',
        ];

        $this->app['config']->set('blade-icons.sets', $sets);
    }

    private function registerRouteMacros(): void
    {
        Route::macro(
            name: 'webauthn',
            macro: function (
                string $prefix = 'sessions/webauthn',
                array $assertionMiddleware = [ValidateSignature::class],
                array $attestationMiddleware = [FilamentAuthenticate::class],
            ) {
                Route::as('profile-filament::')
                    ->group(function () use ($prefix, $assertionMiddleware, $attestationMiddleware) {
                        Route::post("/{$prefix}/assertion-pk/{user}", [WebauthnPublicKeysController::class, 'assertionPublicKey'])
                            ->name('webauthn.assertion_pk')
                            ->middleware($assertionMiddleware);

                        Route::post("/{$prefix}/passkey-assertion-pk", [PasskeysController::class, 'assertionPublicKey'])
                            ->name('webauthn.passkey_assertion_pk')
                            ->middleware($assertionMiddleware);

                        Route::post("/{$prefix}/attestation-pk", [WebauthnPublicKeysController::class, 'attestationPublicKey'])
                            ->name('webauthn.attestation_pk')
                            ->middleware($attestationMiddleware);

                        Route::post("/{$prefix}/passkey-attestation-pk", [PasskeysController::class, 'attestationPublicKey'])
                            ->name('webauthn.passkey_attestation_pk')
                            ->middleware($attestationMiddleware);
                    });
            });
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('masked-value', PackageLivewire\MaskedValue::class);
        Livewire::component('recovery-codes', PackageLivewire\TwoFactorAuthentication\RecoveryCodes::class);
        Livewire::component('authenticator-app-form', PackageLivewire\TwoFactorAuthentication\AuthenticatorAppForm::class);
        Livewire::component('authenticator-app-list-item', PackageLivewire\TwoFactorAuthentication\AuthenticatorAppListItem::class);
        Livewire::component('webauthn-keys', PackageLivewire\TwoFactorAuthentication\WebauthnKeys::class);
        Livewire::component('webauthn-key', PackageLivewire\TwoFactorAuthentication\WebauthnKey::class);
        Livewire::component('passkey', PackageLivewire\Passkey::class);
        Livewire::component('mfa-challenge', MfaChallenge::class);
        Livewire::component('sudo-challenge', SudoChallenge::class);

        Livewire::component(PackageLivewire\Profile\ProfileInfo::class, PackageLivewire\Profile\ProfileInfo::class);
        Livewire::component(PackageLivewire\Emails\UserEmail::class, PackageLivewire\Emails\UserEmail::class);
        Livewire::component(PackageLivewire\DeleteAccount::class, PackageLivewire\DeleteAccount::class);
        Livewire::component(PackageLivewire\UpdatePassword::class, PackageLivewire\UpdatePassword::class);
        Livewire::component(PackageLivewire\PasskeyManager::class, PackageLivewire\PasskeyManager::class);
        Livewire::component(PackageLivewire\MfaOverview::class, PackageLivewire\MfaOverview::class);
        Livewire::component(PackageLivewire\Sessions\SessionManager::class, PackageLivewire\Sessions\SessionManager::class);
    }
}
