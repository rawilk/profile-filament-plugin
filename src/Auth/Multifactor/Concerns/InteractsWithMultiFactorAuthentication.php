<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Concerns;

use Illuminate\Database\Eloquent\Model;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\HasAppAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts\HasWebauthn;

/**
 * @property bool $two_factor_enabled
 * @property string|null $preferred_mfa_provider
 *
 * @mixin Model
 */
trait InteractsWithMultiFactorAuthentication
{
    public function hasMultiFactorAuthenticationEnabled(): bool
    {
        return (bool) $this->two_factor_enabled;
    }

    public function toggleMultiFactorAuthenticationStatus(bool $condition): void
    {
        if ($condition === $this->two_factor_enabled) {
            return;
        }

        $this->two_factor_enabled = $condition;
        $this->save();
    }

    public function getPreferredMfaProvider(): ?string
    {
        return $this->preferred_mfa_provider;
    }

    public function setPreferredMfaProvider(?string $id): void
    {
        if ($id === $this->preferred_mfa_provider) {
            return;
        }

        $this->preferred_mfa_provider = $id;
        $this->save();
    }

    /**
     * This method should be overridden in applications with custom providers defined.
     */
    public function hasOtherEnabledMultiFactorProviders(): bool
    {
        if ($this instanceof HasEmailAuthentication && $this->hasEmailAuthentication()) {
            return true;
        }

        if ($this instanceof HasAppAuthentication && $this->authenticatorApps()->exists()) {
            return true;
        }

        if ($this instanceof HasWebauthn && $this->securityKeys()->exists()) {
            return true;
        }

        return false;
    }

    protected function initializeInteractsWithMultiFactorAuthentication(): void
    {
        $this->mergeCasts([
            'two_factor_enabled' => 'boolean',
        ]);
    }
}
