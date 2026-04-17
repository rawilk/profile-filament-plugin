<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface HasAppAuthentication
{
    public function authenticatorApps(): HasMany;

    public function getAppAuthenticationHolderName(): string;
}
