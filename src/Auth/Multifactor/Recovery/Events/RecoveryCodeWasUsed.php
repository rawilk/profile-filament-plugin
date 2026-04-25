<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

class RecoveryCodeWasUsed extends ProfileFilamentEvent
{
    public function __construct(Authenticatable $user)
    {
    }
}
