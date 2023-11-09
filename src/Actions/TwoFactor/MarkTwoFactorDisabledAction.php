<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorDisabledAction as MarkTwoFactorDisabledActionContract;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationWasDisabled;
use Rawilk\ProfileFilament\Features;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class MarkTwoFactorDisabledAction implements MarkTwoFactorDisabledActionContract
{
    protected Features $features;

    public function __construct()
    {
        $this->features = filament(ProfileFilamentPlugin::make()->getId())->panelFeatures();
    }

    public function __invoke(User $user): void
    {
        if ($this->hasOtherTwoFactorEnabled($user)) {
            return;
        }

        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_recovery_codes' => null,
        ])->save();

        TwoFactorAuthenticationWasDisabled::dispatch($user);
    }

    protected function hasOtherTwoFactorEnabled(User $user): bool
    {
        if ($this->hasAuthenticatorApps($user)) {
            return true;
        }

        if ($this->hasWebauthnKeys($user)) {
            return true;
        }

        return false;
    }

    protected function hasAuthenticatorApps(User $user): bool
    {
        if (! $this->features->hasAuthenticatorApps()) {
            return false;
        }

        return $user->authenticatorApps()->exists();
    }

    protected function hasWebauthnKeys(User $user): bool
    {
        if (! $this->features->hasWebauthn()) {
            return false;
        }

        return $user->webauthnKeys()->exists();
    }
}
