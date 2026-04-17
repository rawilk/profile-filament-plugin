<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Model
 */
trait InteractsWithAppAuthentication
{
    public function authenticatorApps(): HasMany
    {
        return $this->hasMany(config('profile-filament.models.authenticator_app'))
            ->latest();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }
}
