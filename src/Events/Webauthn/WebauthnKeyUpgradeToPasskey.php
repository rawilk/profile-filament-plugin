<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;
use Rawilk\ProfileFilament\Models\WebauthnKey;

final class WebauthnKeyUpgradeToPasskey extends ProfileFilamentEvent
{
    public function __construct(
        public Authenticatable $user,
        public WebauthnKey $passkey,
        public WebauthnKey $upgradedFrom,
    ) {}
}
