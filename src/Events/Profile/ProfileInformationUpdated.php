<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events\Profile;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

final class ProfileInformationUpdated extends ProfileFilamentEvent
{
    public function __construct(public User $user) {}
}
