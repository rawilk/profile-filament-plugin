<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\Concerns;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Arr;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use SensitiveParameter;

trait HasAuthChecks
{
    protected function getAttemptAuthCallback(): array|Closure
    {
        return ProfileFilament::plugin()->getAuthAttemptCallback();
    }

    protected function hasValidCredentials(
        UserProvider $userProvider,
        ?Authenticatable $user,
        #[SensitiveParameter] $credentials,
    ): bool {
        return (! is_null($user)) && $userProvider->validateCredentials($user, $credentials);
    }

    /**
     * @throws \Rawilk\ProfileFilament\Auth\Exceptions\LoginException
     */
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

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function fireAttemptingEvent(Guard $guard, #[SensitiveParameter] array $credentials, bool $remember = false): void
    {
        event(app(Attempting::class, [
            'guard' => property_exists($guard, 'name') ? $guard->name : '',
            'credentials' => $credentials,
            'remember' => $remember,
        ]));
    }
}
