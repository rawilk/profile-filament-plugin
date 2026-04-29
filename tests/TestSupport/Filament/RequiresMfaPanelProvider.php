<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Tests\TestSupport\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\EmailAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\WebauthnProvider;
use Rawilk\ProfileFilament\Auth\Sudo\App\SudoAppAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Email\SudoEmailAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Password\SudoPasswordProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Webauthn\SudoWebauthnProvider;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

final class RequiresMfaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('requires-mfa')
            ->path('requires-mfa')
            ->login()
            ->passwordReset()
            ->emailChangeVerification()
            ->emailVerification()
            ->plugin(
                ProfileFilamentPlugin::make()
                    ->multiFactorAuthentication([
                        AppAuthenticationProvider::make(),
                        WebauthnProvider::make(),
                        EmailAuthenticationProvider::make(),
                    ], isRequired: true)
                    ->sudoMode([
                        SudoAppAuthenticationProvider::make(),
                        SudoWebauthnProvider::make(),
                        SudoEmailAuthenticationProvider::make(),
                        SudoPasswordProvider::make(),
                    ])
                    ->passkeyLogin()
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
