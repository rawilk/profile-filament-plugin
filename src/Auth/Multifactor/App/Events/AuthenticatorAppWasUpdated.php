<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class AuthenticatorAppWasUpdated extends ProfileFilamentEvent
{
    public function __construct(public AuthenticatorApp $authenticatorApp, public User $user)
    {
    }
}
