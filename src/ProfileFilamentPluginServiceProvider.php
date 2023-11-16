<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Psr\Log\NullLogger;
use Rawilk\ProfileFilament\Livewire\MaskedValue;
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

    public function packageRegistered(): void
    {
        $this->app->scoped(
            Contracts\AuthenticatorAppService::class,
            Services\AuthenticatorAppService::class,
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

        Livewire::component('masked-value', MaskedValue::class);
    }

    private function makeClassBindings(): void
    {
        $this->app->bind(Contracts\UpdatePasswordAction::class, fn ($app) => $app->make(config('profile-filament.actions.update_password')));

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
}
