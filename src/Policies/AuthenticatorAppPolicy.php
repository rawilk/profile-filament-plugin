<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Authorizable as User;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class AuthenticatorAppPolicy
{
    use HandlesAuthorization;

    public function edit(User $user, AuthenticatorApp $authenticatorApp): Response
    {
        return $user->id === $authenticatorApp->user_id
            ? Response::allow()
            : Response::deny();
    }

    public function delete(User $user, AuthenticatorApp $authenticatorApp): Response
    {
        return $user->id === $authenticatorApp->user_id
            ? Response::allow()
            : Response::deny();
    }
}
