<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Events\Sudo;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

final class SudoModeChallenged extends ProfileFilamentEvent
{
    public function __construct(public User $user, public Request $request) {}
}
