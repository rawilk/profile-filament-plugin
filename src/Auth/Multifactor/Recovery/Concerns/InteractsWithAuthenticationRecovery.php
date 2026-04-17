<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use SensitiveParameter;

/**
 * @property array<string>|null $two_factor_recovery_codes
 *
 * @mixin Model
 */
trait InteractsWithAuthenticationRecovery
{
    public function getAuthenticationRecoveryCodes(): ?array
    {
        return $this->two_factor_recovery_codes;
    }

    public function saveAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->two_factor_recovery_codes = $codes;
        $this->save();
    }

    protected function initializeInteractsWithAuthenticationRecovery(): void
    {
        $this->mergeCasts([
            'two_factor_recovery_codes' => 'encrypted:array',
        ]);

        if (version_compare(Application::VERSION, '12.25.0', '>=')) {
            $this->mergeHidden([
                'two_factor_recovery_codes',
            ]);
        } else {
            $this->hidden = array_values(array_unique(array_merge($this->hidden, [
                'two_factor_recovery_codes',
            ])));
        }
    }
}
