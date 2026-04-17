<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Contracts;

interface HasMultiFactorAuthentication
{
    public function hasMultiFactorAuthenticationEnabled(): bool;

    public function toggleMultiFactorAuthenticationStatus(bool $condition): void;

    public function getPreferredMfaProvider(): ?string;

    public function setPreferredMfaProvider(?string $id): void;

    /**
     * Indicates if the user has at least one other enabled multifactor authentication provider.
     */
    public function hasOtherEnabledMultiFactorProviders(): bool;
}
