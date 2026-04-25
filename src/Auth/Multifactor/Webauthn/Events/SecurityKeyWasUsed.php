<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class SecurityKeyWasUsed extends ProfileFilamentEvent
{
    public function __construct(public User $user, public WebauthnKey $webauthnKey, public Request $request)
    {
    }
}
