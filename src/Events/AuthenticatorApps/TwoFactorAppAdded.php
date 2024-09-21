<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events\AuthenticatorApps;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

final class TwoFactorAppAdded extends ProfileFilamentEvent
{
    public function __construct(public User $user, public AuthenticatorApp $authenticatorApp) {}
}
