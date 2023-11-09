<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Enums\Livewire\SensitiveProfileSection;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class WebauthnKeyPolicy
{
    use HandlesAuthorization;

    public function edit(Authenticatable $user, WebauthnKey $webauthnKey): Response
    {
        if (! ProfileFilament::shouldShowProfileSection(SensitiveProfileSection::Mfa->value)) {
            return Response::deny();
        }

        return $user->id === $webauthnKey->user_id
            ? Response::allow()
            : Response::deny();
    }

    public function delete(Authenticatable $user, WebauthnKey $webauthnKey): Response
    {
        if (! ProfileFilament::shouldShowProfileSection(SensitiveProfileSection::Mfa->value)) {
            return Response::deny();
        }

        return $user->id === $webauthnKey->user_id
            ? Response::allow()
            : Response::deny();
    }

    public function upgradeToPasskey(Authenticatable $user, WebauthnKey $webauthnKey): Response
    {
        if (! ProfileFilament::shouldShowProfileSection(SensitiveProfileSection::Mfa->value)) {
            return Response::deny();
        }

        if (! $webauthnKey->canUpgradeToPasskey()) {
            return Response::deny();
        }

        return $user->id === $webauthnKey->user_id
            ? Response::allow()
            : Response::deny();
    }
}
