<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Rawilk\ProfileFilament\Support\Config;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait InteractsWithWebauthn
{
    public function securityKeys(): HasMany
    {
        return $this->hasMany(Config::getModel('webauthn_key'))
            ->latest();
    }

    public function getPasskeyName(): string
    {
        return $this->email;
    }

    public function getPasskeyId(): string
    {
        return (string) $this->getRouteKey();
    }

    public function getPasskeyDisplayName(): string
    {
        return $this->getPasskeyName();
    }
}
