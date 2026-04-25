<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class SecurityKeyWasDeleted extends ProfileFilamentEvent
{
    public function __construct(public WebauthnKey $webauthnKey, public User $user)
    {
    }
}
