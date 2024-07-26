<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Services;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;
use Rawilk\ProfileFilament\Contracts\AuthenticatorAppService as AuthenticatorAppServiceContract;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppUsed;
use Rawilk\ProfileFilament\Events\RecoveryCodeReplaced;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationChallenged;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Symfony\Component\HttpFoundation\Response;

/**
 * Note: Webauthn security key verification is handled through the Webauthn service class.
 */
class Mfa
{
    use Macroable;

    /**
     * The user attempting a two-factor challenge. This will typically be set
     * during login mfa challenges.
     */
    protected ?User $challengedUser = null;

    /**
     * Indicates if the user wished to be remembered after login.
     */
    protected ?bool $remember = null;

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $userModel
     */
    public function __construct(protected string $userModel) {}

    public function usingChallengedUser(?User $user): self
    {
        $this->challengedUser = $user;

        return $this;
    }

    public function confirmUserSession(User $user): void
    {
        session()->forget([
            MfaSession::User->value,
            MfaSession::Remember->value,
        ]);

        session()->put($this->getUserConfirmedKey($user), true);
    }

    public function isConfirmedInSession(User $user): bool
    {
        $key = $this->getUserConfirmedKey($user);

        return session()->has($key)
            && session()->get($key) === true;
    }

    public function hasChallengedUser(): bool
    {
        return session()->has(MfaSession::User->value)
            && $this->userModel::query()
                ->withoutGlobalScopes()
                ->whereKey(session()->get(MfaSession::User->value))
                ->exists();
    }

    public function challengedUser(): Model|User
    {
        if ($this->challengedUser) {
            return $this->challengedUser;
        }

        $user = $this->userModel::query()
            ->withoutGlobalScopes()
            ->whereKey(session()->get(MfaSession::User->value))
            ->first();

        abort_unless(
            $user,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            __('profile-filament::messages.mfa_challenge.invalid_challenged_user'),
        );

        /** @phpstan-ignore-next-line */
        return $this->challengedUser = $user;
    }

    public function pushChallengedUser(User $user, bool $remember = false): void
    {
        session()->put([
            MfaSession::User->value => $user->getAuthIdentifier(),
            MfaSession::Remember->value => $remember,
        ]);

        TwoFactorAuthenticationChallenged::dispatch($user);
    }

    public function isValidRecoveryCode(string $code): bool
    {
        if (blank($code)) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        $validCode = collect($this->challengedUser()->recoveryCodes())
            ->first(fn (string $storedCode) => hash_equals($code, $storedCode) ? $code : null);

        if (! $validCode) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        $newCode = $this->challengedUser()->replaceRecoveryCode($validCode);

        RecoveryCodeReplaced::dispatch($this->challengedUser(), $validCode, $newCode);

        return true;
    }

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

    public function canUseAuthenticatorAppsForChallenge(?User $user = null): bool
    {
        if (
            Filament::getCurrentPanel()
                && ! $this->profilePlugin()->panelFeatures()->hasAuthenticatorApps()
        ) {
            return false;
        }

        $user ??= $this->challengedUser();

        return app(config('profile-filament.models.authenticator_app'))::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }

    public function canUseWebauthnForChallenge(?User $user = null): bool
    {
        if (
            Filament::getCurrentPanel()
                && ! $this->hasWebauthnOrPasskeys()
        ) {
            return false;
        }

        $user ??= $this->challengedUser();

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
            $this->remember = session()->pull(MfaSession::Remember->value, false);
        }

        return $this->remember;
    }

    protected function getUserConfirmedKey(User $user): string
    {
        return MfaSession::Confirmed->value . ".{$user->getAuthIdentifier()}";
    }

    protected function profilePlugin(): ProfileFilamentPlugin
    {
        /** @phpstan-ignore-next-line */
        return Filament::getPlugin(ProfileFilamentPLugin::PLUGIN_ID);
    }

    protected function hasWebauthnOrPasskeys(): bool
    {
        return $this->profilePlugin()->panelFeatures()->hasWebauthn()
            || $this->profilePlugin()->panelFeatures()->hasPasskeys();
    }
}
