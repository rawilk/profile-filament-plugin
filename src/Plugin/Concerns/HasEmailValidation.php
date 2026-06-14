<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Closure;
use Rawilk\ProfileFilament\Support\Config;

trait HasEmailValidation
{
    protected bool|Closure|null $shouldEnforceUniqueEmail = null;

    public function enforceUniqueEmail(bool|Closure $condition = true): static
    {
        $this->shouldEnforceUniqueEmail = $condition;

        return $this;
    }

    public function isEnforcingUniqueEmail(): bool
    {
        $condition = $this->evaluate($this->shouldEnforceUniqueEmail);

        if (is_bool($condition)) {
            return $condition;
        }

        return Config::shouldAddUniqueEmailConstraint();
    }
}
