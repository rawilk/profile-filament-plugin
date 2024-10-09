<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;
use Rawilk\ProfileFilament\Models\WebauthnKey;

final class WebauthnKeyUsed extends ProfileFilamentEvent
{
    public function __construct(public User $user, public WebauthnKey $webauthnKey) {}
}
