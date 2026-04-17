<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts\EmailAuthentication;

use Illuminate\Contracts\Auth\Authenticatable;

interface EnableEmailAuthenticationAction
{
    public function __invoke(Authenticatable $user);
}
