<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs;

use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Collection;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;

class PreferredMultiFactorProviderSelect
{
    /**
     * @param  Collection<string, MultiFactorAuthenticationProvider>  $enabledMultiFactorProviders
     */
    public static function make(Collection $enabledMultiFactorProviders, HasMultiFactorAuthentication $user): Component
    {
        return Select::make('preferred_mfa_provider')
            ->label(__('profile-filament::pages/security.mfa.form.preferred_mfa_provider.label'))
            ->placeholder(__('profile-filament::pages/security.mfa.form.preferred_mfa_provider.placeholder'))
            ->belowContent(__('profile-filament::pages/security.mfa.form.preferred_mfa_provider.description'))
            ->options(
                $enabledMultiFactorProviders->mapWithKeys(
                    fn (MultiFactorAuthenticationProvider $provider) => [$provider->getId() => $provider->getSelectLabel()]
                )
            )
            ->live()
            ->visible(fn (): bool => $enabledMultiFactorProviders->count() > 1)
            ->default($user->getPreferredMfaProvider())
            ->afterStateUpdated(function ($state) use ($user) {
                $user->setPreferredMfaProvider($state);

                Notification::make()
                    ->success()
                    ->title(__('profile-filament::pages/security.mfa.form.preferred_mfa_provider.notifications.saved.title'))
                    ->send();
            });
    }
}
