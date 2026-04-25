<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

class EmailAuthenticationWasEnabled extends ProfileFilamentEvent
{
    public function __construct(public Authenticatable $user)
    {
    }
}
