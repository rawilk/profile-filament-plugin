<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Contracts\DeleteAccountAction as DeleteAccountActionContract;
use Rawilk\ProfileFilament\Events\UserDeletedAccount;

class DeleteAccountAction implements DeleteAccountActionContract
{
    public function __invoke(User $user)
    {
        $user->delete();

        UserDeletedAccount::dispatch($user);
    }
}
