<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Contracts\UpdatePasswordAction as UpdatePasswordActionContract;
use Rawilk\ProfileFilament\Events\UserPasswordWasUpdated;

class UpdatePasswordAction implements UpdatePasswordActionContract
{
    public function __invoke(User $user, string $newPassword): void
    {
        $user->update([
            'password' => $newPassword,
        ]);

        UserPasswordWasUpdated::dispatch($user);
    }
}
