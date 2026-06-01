<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Providers\Concerns;

use Livewire\Livewire;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Livewire\AuthenticatorAppActions;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\MultiFactorChallenge;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\SetUpRequiredMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Livewire\MultiFactorAuthenticationManager;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Pages\CrossDomainSecurityKeyRegistration;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Pages\CrossDomainWebauthnAuth;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Livewire\SecurityKeyActions;
use Rawilk\ProfileFilament\Auth\Sudo\Filament\SudoChallenge;
use Rawilk\ProfileFilament\Auth\Sudo\Livewire\SudoChallengeActionForm;
use Rawilk\ProfileFilament\Livewire as PackageLivewire;

trait HasComponents
{
    protected static array $livewireComponents = [
        'sudo-challenge-action-form' => SudoChallengeActionForm::class,
        'authenticator-app-actions' => AuthenticatorAppActions::class,
        'security-key-actions' => SecurityKeyActions::class,

        PackageLivewire\Profile\ProfileInfo::class => PackageLivewire\Profile\ProfileInfo::class,
        PackageLivewire\Emails\UserEmail::class => PackageLivewire\Emails\UserEmail::class,
        PackageLivewire\DeleteAccount::class => PackageLivewire\DeleteAccount::class,
        PackageLivewire\UpdatePassword::class => PackageLivewire\UpdatePassword::class,
        PackageLivewire\Sessions\SessionManager::class => PackageLivewire\Sessions\SessionManager::class,

        MultiFactorAuthenticationManager::class => MultiFactorAuthenticationManager::class,
        MultiFactorChallenge::class => MultiFactorChallenge::class,
        SudoChallenge::class => SudoChallenge::class,
        CrossDomainWebauthnAuth::class => CrossDomainWebauthnAuth::class,
        CrossDomainSecurityKeyRegistration::class => CrossDomainSecurityKeyRegistration::class,
        SetUpRequiredMultiFactorAuthentication::class => SetUpRequiredMultiFactorAuthentication::class,
    ];

    protected function registerLivewireComponents(): void
    {
        foreach (static::$livewireComponents as $componentName => $componentClass) {
            Livewire::component($componentName, $componentClass);
        }
    }
}
