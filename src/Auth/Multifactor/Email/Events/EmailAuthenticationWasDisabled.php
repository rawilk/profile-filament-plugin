<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

class EmailAuthenticationWasDisabled extends ProfileFilamentEvent
{
    public function __construct(Authenticatable $user)
    {
    }
}
