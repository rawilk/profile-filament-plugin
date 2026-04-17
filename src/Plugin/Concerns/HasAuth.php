<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\AttemptToAuthenticateUser;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\PrepareAuthenticatedSession;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\RedirectIfHasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\ResolveUser;

trait HasAuth
{
    /**
     * A callback to run when logging a user in to determine if they are allowed to login.
     */
    protected array|Closure|null $authAttemptCallback = null;

    protected array|Closure|null $loginPipes = null;

    public function attemptAuthWith(array|Closure|null $callback = null): static
    {
        $this->authAttemptCallback = $callback;

        return $this;
    }

    public function sendLoginThrough(array|Closure|null $pipes): static
    {
        $this->loginPipes = $pipes;

        return $this;
    }

    public function getAuthAttemptCallback(): array|Closure
    {
        if ($this->authAttemptCallback !== null) {
            return $this->authAttemptCallback;
        }

        return function (Authenticatable $user): bool {
            if (! ($user instanceof FilamentUser)) {
                return true;
            }

            return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
        };
    }

    public function getLoginPipes(): array
    {
        return $this->evaluate($this->loginPipes) ?? [
            ResolveUser::class,
            RedirectIfHasMultiFactorAuthentication::class,
            AttemptToAuthenticateUser::class,
            PrepareAuthenticatedSession::class,
        ];
    }
}
