<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts\PendingUserEmail;

use Illuminate\Contracts\Auth\Authenticatable as User;

interface StoreOldUserEmailAction
{
    public function __invoke(User $user, string $email);
}
