<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts;

use Illuminate\Contracts\Auth\Authenticatable as User;

interface StoreAuthenticatorAppAction
{
    public function __invoke(User $user, string $name, string $secret);
}
