<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events\PendingUserEmails;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

final class EmailAddressReverted extends ProfileFilamentEvent
{
    public function __construct(
        public User $user,
        public string $revertedFrom,
        public string $revertedTo,
    ) {}
}
