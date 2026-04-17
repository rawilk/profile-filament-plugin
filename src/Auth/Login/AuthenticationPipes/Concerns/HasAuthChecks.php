<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Arr;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use SensitiveParameter;

trait HasAuthChecks
{
    protected function getAttemptAuthCallback(): array|Closure
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID)->getAuthAttemptCallback();
    }

    protected function hasValidCredentials(
        UserProvider $userProvider,
        ?Authenticatable $user,
        #[SensitiveParameter] $credentials,
    ): bool {
        return (! is_null($user)) && $userProvider->validateCredentials($user, $credentials);
    }

    protected function shouldLogin(Authenticatable $user, ?StatefulGuard $guard = null): bool
    {
        $guard ??= Filament::auth();

        foreach (Arr::wrap($this->getAttemptAuthCallback()) as $callback) {
            if (! $callback($user, $guard)) {
                return false;
            }
        }

        return true;
    }
}
