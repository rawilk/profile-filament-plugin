<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

trait HasTenancy
{
    protected bool|Closure $isTenantAware = false;

    protected ?Closure $profileTenantResolver = null;

    public function tenantAware(bool|Closure $condition = true): static
    {
        $this->isTenantAware = $condition;

        return $this;
    }

    public function isTenantAware(): bool
    {
        return (bool) $this->evaluate($this->isTenantAware);
    }

    public function resolveTenantUsing(?Closure $callback): static
    {
        $this->profileTenantResolver = $callback;

        return $this;
    }

    public function resolveTenant(?Authenticatable $user = null): ?Model
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if (! $panel->hasTenancy()) {
            return null;
        }

        $user ??= Filament::auth()->user();

        if (! $user) {
            return null;
        }

        if ($this->profileTenantResolver) {
            return $this->evaluate(
                $this->profileTenantResolver,
                namedInjections: [
                    'panel' => $panel,
                    'user' => $user,
                ],
                typedInjections: [
                    Panel::class => $panel,
                    Authenticatable::class => $user,
                    $user::class => $user,
                ],
            );
        }

        if (! $user instanceof HasTenants) {
            return null;
        }

        return Filament::getUserDefaultTenant($user);
    }
}
