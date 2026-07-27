<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Closure;

trait HasTenancy
{
    protected bool|Closure $isTenantAware = false;

    public function tenantAware(bool|Closure $condition = true): static
    {
        $this->isTenantAware = $condition;

        return $this;
    }

    public function isTenantAware(): bool
    {
        return (bool) $this->evaluate($this->isTenantAware);
    }
}
