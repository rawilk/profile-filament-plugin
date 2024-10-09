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

    public function before(User $user, string $ability, string|AuthenticatorApp $authenticatorApp)
    {
        if (! is_string($authenticatorApp) && $authenticatorApp->user()->isNot($user)) {
            return Response::deny();
        }
    }

    public function update(User $user, AuthenticatorApp $authenticatorApp): Response
    {
        return Response::allow();
    }

    public function delete(User $user, AuthenticatorApp $authenticatorApp): Response
    {
        return Response::allow();
    }
}
