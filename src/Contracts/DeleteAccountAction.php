<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts;

use Illuminate\Contracts\Auth\Authenticatable as User;

interface DeleteAccountAction
{
    public function __invoke(User $user);
}
