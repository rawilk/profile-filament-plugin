<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Contracts;

use Illuminate\Contracts\Auth\Authenticatable as User;

interface MarkMultiFactorDisabledAction
{
    public function __invoke(User $user);
}
