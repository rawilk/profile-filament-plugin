<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts\AuthenticatorApps;

use Illuminate\Contracts\Auth\Authenticatable as User;

interface ConfirmTwoFactorAppAction
{
    public function __invoke(User $user, string $name, string $secret);
}
