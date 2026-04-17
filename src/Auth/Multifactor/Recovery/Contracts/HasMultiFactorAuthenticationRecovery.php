<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts;

use SensitiveParameter;

interface HasMultiFactorAuthenticationRecovery
{
    /**
     * @return array<string>|null
     */
    public function getAuthenticationRecoveryCodes(): ?array;

    /**
     * @param  array<string>|null  $codes
     */
    public function saveAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void;
}
