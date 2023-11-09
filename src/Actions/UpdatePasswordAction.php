<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Auth;
use Rawilk\ProfileFilament\Contracts\UpdatePasswordAction as UpdatePasswordActionContract;
use Rawilk\ProfileFilament\Events\UserPasswordWasUpdated;

class UpdatePasswordAction implements UpdatePasswordActionContract
{
    public function __invoke(User $user, string $newPassword): void
    {
        $user->update([
            'password' => $newPassword,
        ]);

        if (request()?->hasSession()) {
            session()->put([
                "password_hash_{$this->getGuard()}" => $user->getAuthPassword(),
            ]);
        }

        UserPasswordWasUpdated::dispatch($user);
    }

    protected function getGuard(): string
    {
        return Filament::getCurrentPanel()?->getAuthGuard()
            ?? Auth::getDefaultDriver();
    }
}
