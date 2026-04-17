<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts\EmailAuthentication;

use Illuminate\Contracts\Auth\Authenticatable;

interface DisableEmailAuthenticationAction
{
    public function __invoke(Authenticatable $user);
}
