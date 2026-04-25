<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface DisableEmailAuthenticationAction
{
    public function __invoke(Authenticatable $user);
}
