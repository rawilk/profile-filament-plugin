<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Services;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Rawilk\ProfileFilament\Auth\Multifactor\Enums\MfaSession;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationChallenged;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class Mfa
{
    use Macroable;

    /**
     * The user attempting a multi-factor challenge.
     */
    protected ?User $challengedUser = null;

    /**
     * Indicates if the user wished to be remembered after login.
     */
    protected ?bool $remember = null;

    protected UserProvider $userProvider;

    public function __construct()
    {
        $this->userProvider = Filament::auth()->getProvider() ?? auth()->guard('web')->getProvider();
    }

    public function confirmUserSession(User $user): void
    {
        $this->flushPendingSession();

        session()->put($this->getUserConfirmedKey($user), now()->unix());
    }

    public function isConfirmedInSession(User $user): bool
    {
        $key = $this->getUserConfirmedKey($user);

        return session()->has($key)
            && session()->get($key) === true;
    }

    public function challengedUser(): Model|User|null
    {
        if ($this->challengedUser) {
            return $this->challengedUser;
        }

        if (! MfaSession::UserBeingAuthenticated->has()) {
            return null;
        }

        return $this->userProvider->retrieveById(
            MfaSession::UserBeingAuthenticated->get()
        );
    }

    /**
     * Indicates if too long has passed since the user confirmed their password
     * on the login form.
     */
    public function passwordConfirmationHasExpired(): bool
    {
        if (! MfaSession::PasswordConfirmedAt->has()) {
            return true;
        }

        // @todo: Make expiration configurable per panel
        return Date::createFromTimestamp(MfaSession::PasswordConfirmedAt->get())
            ->addMinutes(15)
            ->isPast();
    }

    public function pushChallengedUser(User $user, bool $remember = false): void
    {
        MfaSession::UserBeingAuthenticated->set((string) $user->getAuthIdentifier());
        MfaSession::PasswordConfirmedAt->set(now()->unix());
        MfaSession::Remember->set($remember);

        TwoFactorAuthenticationChallenged::dispatch($user);
    }

    public function flushPendingSession(): void
    {
        session()->forget([
            MfaSession::UserBeingAuthenticated,
            MfaSession::PasswordConfirmedAt,
            MfaSession::Remember,
        ]);
    }

    /**
     * Determine if the user wanted to be remembered after login.
     */
    public function remember(): bool
    {
        if (is_null($this->remember)) {
            $this->remember = MfaSession::Remember->isTrue();
        }

        return $this->remember;
    }

    protected function getUserConfirmedKey(User $user): string
    {
        return Str::of(MfaSession::ConfirmedAt->value)
            ->append("__{$user->getAuthIdentifier()}")
            ->value();
    }

    protected function profilePlugin(): ProfileFilamentPlugin
    {
        return Filament::getPlugin(ProfileFilamentPlugin::PLUGIN_ID);
    }
}
