<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Login\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

class PreparingAuthenticatedSession extends ProfileFilamentEvent
{
    public function __construct(public Authenticatable $user)
    {
    }
}
