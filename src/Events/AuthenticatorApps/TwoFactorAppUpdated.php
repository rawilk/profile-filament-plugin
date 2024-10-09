<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events\AuthenticatorApps;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

final class TwoFactorAppUpdated extends ProfileFilamentEvent
{
    public function __construct(public AuthenticatorApp $authenticatorApp, public User $user) {}
}
