<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Services;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Rawilk\ProfileFilament\Contracts\AuthenticatorAppService as AuthenticatorAppServiceContract;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppUsed;
use Rawilk\ProfileFilament\Events\RecoveryCodeReplaced;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationChallenged;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class Mfa
{
    use Macroable;

    /**
     * The user attempting a two-factor challenge.
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

    /** @deprecated */
    public function usingChallengedUser(?User $user): self
    {
        $this->challengedUser = $user;

        return $this;
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

    /** @deprecated */
    public function hasChallengedUser(): bool
    {
        return MfaSession::UserBeingAuthenticated->has()
            && $this->userModel::query()
                ->withoutGlobalScopes()
                ->whereKey(MfaSession::UserBeingAuthenticated->get())
                ->exists();
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

    /** @deprecated  */
    public function isValidRecoveryCode(string $code): bool
    {
        if (blank($code)) {
            return false;
        }

        $validCode = collect($this->challengedUser()->recoveryCodes())
            ->first(fn (string $storedCode) => hash_equals($code, $storedCode) ? $code : null);

        if (! $validCode) {
            return false;
        }

        $newCode = $this->challengedUser()->replaceRecoveryCode($validCode);

        RecoveryCodeReplaced::dispatch($this->challengedUser(), $validCode, $newCode);

        return true;
    }

    /** @deprecated */
    public function isValidTotpCode(string $code): bool
    {
        $authenticatorApps = app(AuthenticatorApp::class)::query()
            ->where('user_id', $this->challengedUser()->getAuthIdentifier())
            ->get(['id', 'secret']);

        foreach ($authenticatorApps as $authenticatorApp) {
            if (app(AuthenticatorAppServiceContract::class)->verify($authenticatorApp->secret, $code)) {
                $authenticatorApp->update(['last_used_at' => now()]);

                TwoFactorAppUsed::dispatch($this->challengedUser(), $authenticatorApp);

                return true;
            }
        }

        return false;
    }

    /** @deprecated */
    public function canUseAuthenticatorAppsForChallenge(?User $user = null): bool
    {
        if (
            Filament::getCurrentPanel()
                && ! $this->profilePlugin()->panelFeatures()->hasAuthenticatorApps()
        ) {
            return false;
        }

        $user ??= $this->challengedUser();

        if (! $this->userHasMfaEnabled($user)) {
            return false;
        }

        return app(config('profile-filament.models.authenticator_app'))::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }

    /** @deprecated */
    public function canUseWebauthnForChallenge(?User $user = null): bool
    {
        if (
            Filament::getCurrentPanel()
                && ! $this->hasWebauthnOrPasskeys()
        ) {
            return false;
        }

        $user ??= $this->challengedUser();

        if (! $this->userHasMfaEnabled($user)) {
            return false;
        }

        return app(config('profile-filament.models.webauthn_key'))::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
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

    /** @deprecated */
    public function userHasMfaEnabled(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (method_exists($user, 'hasTwoFactorEnabled')) {
            return $user->hasTwoFactorEnabled();
        }

        return $user?->two_factor_enabled === true;
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

    /** @deprecated */
    protected function hasWebauthnOrPasskeys(): bool
    {
        return $this->profilePlugin()->panelFeatures()->hasWebauthn()
            || $this->profilePlugin()->panelFeatures()->hasPasskeys();
    }
}
