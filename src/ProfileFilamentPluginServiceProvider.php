<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament;

use BladeUI\Icons\Factory;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Livewire\AuthenticatorAppActions;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\Dto\MultiFactorEventBag;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\Dto\MultiFactorEventBagContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Services\Mfa;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Dto\PasskeyLoginEventBag;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Dto\PasskeyLoginEventBagContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Http\Controllers\AuthenticateUsingPasskeyController;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Http\Controllers\GeneratePasskeyAuthenticationOptionsController;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Livewire\SecurityKeyActions;
use Rawilk\ProfileFilament\Auth\Sudo\Sudo;
use Rawilk\ProfileFilament\Livewire as PackageLivewire;
use Rawilk\ProfileFilament\Responses\BlockEmailChangeVerificationResponse;
use Rawilk\ProfileFilament\Responses\EmailChangeVerificationResponse;
use Rawilk\ProfileFilament\Responses\MultiFactorChallengeResponse;
use Rawilk\ProfileFilament\Support\Config;
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
        $this->registerIcons();

        $this->app->scoped(
            Mfa::class,
            fn () => new Mfa,
        );

        $this->app->scoped(
            Sudo::class,
            fn ($app) => new Sudo(
                expiration: $app['config']['profile-filament.sudo.expires'],
            ),
        );
    }

    private function registerIcons(): void
    {
        $this->callAfterResolving(Factory::class, function (Factory $factory) {
            $factory->add('pf', [
                'path' => __DIR__ . '/../resources/svg',
                'prefix' => 'pf',
            ]);
        });
    }

    private function makeClassBindings(): void
    {
        $this->app->bind(Contracts\UpdatePasswordAction::class, fn ($app) => $app->make(Config::getActionClass('update_password')));
        $this->app->bind(Contracts\DeleteAccountAction::class, fn ($app) => $app->make(Config::getActionClass('delete_account')));

        // General multi-factor
        $this->app->bind(Auth\Multifactor\Contracts\MarkMultiFactorDisabledAction::class, fn ($app) => $app->make(Config::getActionClass('mark_multifactor_disabled')));
        $this->app->bind(Auth\Multifactor\Contracts\MarkMultiFactorEnabledAction::class, fn ($app) => $app->make(Config::getActionClass('mark_multifactor_enabled')));

        // Authenticator apps
        $this->app->bind(Auth\Multifactor\App\Contracts\StoreAuthenticatorAppAction::class, fn ($app) => $app->make(Config::getActionClass('store_authenticator_app')));
        $this->app->bind(Auth\Multifactor\App\Contracts\DeleteAuthenticatorAppAction::class, fn ($app) => $app->make(Config::getActionClass('delete_authenticator_app')));

        // Email authentication
        $this->app->bind(Auth\Multifactor\Email\Contracts\EnableEmailAuthenticationAction::class, fn ($app) => $app->make(Config::getActionClass('enable_email_authentication')));
        $this->app->bind(Auth\Multifactor\Email\Contracts\DisableEmailAuthenticationAction::class, fn ($app) => $app->make(Config::getActionClass('disable_email_authentication')));

        // Pending user emails
        $this->app->bind(Contracts\PendingUserEmail\UpdateUserEmailAction::class, fn ($app) => $app->make(Config::getActionClass('update_user_email')));

        // Responses
        $this->app->bind(Contracts\Responses\EmailChangeVerificationResponse::class, EmailChangeVerificationResponse::class);
        $this->app->bind(Contracts\Responses\MultiFactorChallengeResponse::class, MultiFactorChallengeResponse::class);
        $this->app->bind(Contracts\Responses\BlockEmailVerificationResponse::class, BlockEmailChangeVerificationResponse::class);

        // Dto
        $this->app->bind(MultiFactorEventBagContract::class, MultiFactorEventBag::class);
        $this->app->bind(PasskeyLoginEventBagContract::class, PasskeyLoginEventBag::class);
    }

    private function registerAssets(): void
    {
        FilamentAsset::register(
            assets: [
                Css::make('profile-filament-plugin', __DIR__ . '/../resources/dist/plugin.css')->loadedOnRequest(),
                Js::make('profile-filament-webauthn', __DIR__ . '/../resources/dist/webauthn/webauthn.js'),
            ],
            package: ProfileFilamentPlugin::PLUGIN_ID,
        );
    }

    private function registerRouteMacros(): void
    {
        Route::macro(
            name: 'webauthn',
            macro: function (
                string $prefix = 'sessions/webauthn',
            ) {
                Route::prefix($prefix)
                    ->as('profile-filament::webauthn.')
                    ->group(function () {
                        Route::get('passkey-authentication-options', GeneratePasskeyAuthenticationOptionsController::class)
                            ->name('passkey_authentication_options');

                        Route::post('passkey-authentication', AuthenticateUsingPasskeyController::class)
                            ->name('passkey_authentication')
                            ->middleware(['guest']);
                    });
            });
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('sudo-challenge-action-form', Auth\Sudo\Livewire\SudoChallengeActionForm::class);
        Livewire::component('authenticator-app-actions', AuthenticatorAppActions::class);
        Livewire::component('security-key-actions', SecurityKeyActions::class);

        Livewire::component(PackageLivewire\Profile\ProfileInfo::class, PackageLivewire\Profile\ProfileInfo::class);
        Livewire::component(PackageLivewire\Emails\UserEmail::class, PackageLivewire\Emails\UserEmail::class);
        Livewire::component(PackageLivewire\DeleteAccount::class, PackageLivewire\DeleteAccount::class);
        Livewire::component(PackageLivewire\UpdatePassword::class, PackageLivewire\UpdatePassword::class);
        Livewire::component(PackageLivewire\Sessions\SessionManager::class, PackageLivewire\Sessions\SessionManager::class);
    }
}
