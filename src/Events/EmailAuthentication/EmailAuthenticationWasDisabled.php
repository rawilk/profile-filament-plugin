<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events\EmailAuthentication;

use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

class EmailAuthenticationWasDisabled extends ProfileFilamentEvent
{
    public function __construct(Authenticatable $user)
    {
    }
}
