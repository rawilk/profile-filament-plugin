<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class WebauthnKeyPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability, string|WebauthnKey $webauthnKey)
    {
        if (! is_string($webauthnKey) && $webauthnKey->user()->isNot($user)) {
            return Response::deny();
        }
    }

    public function update(User $user, WebauthnKey $webauthnKey): Response
    {
        return Response::allow();
    }

    public function delete(User $user, WebauthnKey $webauthnKey): Response
    {
        return Response::allow();
    }

    public function upgradeToPasskey(User $user, WebauthnKey $webauthnKey): Response
    {
        return $webauthnKey->canUpgradeToPasskey()
            ? Response::allow()
            : Response::deny();
    }
}
