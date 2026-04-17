<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events;

use Illuminate\Contracts\Auth\Authenticatable;

class RecoveryCodeWasUsed extends ProfileFilamentEvent
{
    public function __construct(Authenticatable $user)
    {
    }
}
