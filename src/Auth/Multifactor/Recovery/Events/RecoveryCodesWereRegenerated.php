<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

class RecoveryCodesWereRegenerated extends ProfileFilamentEvent
{
    public function __construct(public User $user)
    {
    }
}
