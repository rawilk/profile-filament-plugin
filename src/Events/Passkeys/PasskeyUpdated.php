<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events\Passkeys;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class PasskeyUpdated extends ProfileFilamentEvent
{
    public function __construct(public WebauthnKey $passkey, public User $user)
    {
    }
}
