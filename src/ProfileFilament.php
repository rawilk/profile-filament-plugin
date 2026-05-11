<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Auth\Sudo\Contracts\SudoChallengeProvider;
use RuntimeException;
use Throwable;
use Valorin\Random\Random;

class ProfileFilament
{
    /**
     * The callback that is responsible for finding the authenticated user's timezone.
     */
    public static ?Closure $findUserTimezoneUsingCallback = null;

    /**
     * The callback that should be used to create the verify email change url.
     */
    public static ?Closure $createVerifyEmailChangeUrlCallback = null;

    /**
     * The callback that should be used to create the block email change url.
     */
    public static ?Closure $createBlockEmailChangeUrlCallback = null;

    /**
     * The callback that should be used to create the email verification url.
     */
    public static ?Closure $createEmailVerificationUrlCallback = null;

    /**
     * The callback that should be responsible for generating challenges for webauthn requests
     * or other requests that require some sort of challenge.
     */
    public static ?Closure $generateChallengesUsingCallback = null;

    /**
     * The plugin instance for the current panel.
     */
    protected ?ProfileFilamentPlugin $plugin = null;

    /**
     * Register a callback that is responsible for retrieving the authenticated user's timezone.
     */
    public static function findUserTimezoneUsing(?Closure $callback): void
    {
        static::$findUserTimezoneUsingCallback = $callback;
    }

    /**
     * Set a callback that should be used when creating the verify email change url.
     */
    public static function createVerifyEmailChangeUrlUsing(?Closure $callback): void
    {
        static::$createVerifyEmailChangeUrlCallback = $callback;
    }

    /**
     * Set a callback that should be used when creating the block email change url.
     */
    public static function createBlockEmailChangeUrlUsing(?Closure $callback): void
    {
        static::$createBlockEmailChangeUrlCallback = $callback;
    }

    /**
     * Set a callback that should be used when creating the email verification url.
     */
    public static function createEmailVerificationUrlUsing(?Closure $callback): void
    {
        static::$createEmailVerificationUrlCallback = $callback;
    }

    /**
     * Set a callback that should be responsible for generating challenges for webauthn requests
     * or other requests that require some sort of challenge.
     */
    public static function generateChallengesUsing(?Closure $callback): void
    {
        static::$generateChallengesUsingCallback = $callback;
    }

    public function plugin(): ProfileFilamentPlugin
    {
        if ($this->plugin) {
            return $this->plugin;
        }

        $panel = Filament::getCurrentOrDefaultPanel();

        if (! $panel->hasPlugin(ProfileFilamentPlugin::PLUGIN_ID)) {
            throw new LogicException('The ProfileFilamentPlugin is not part of the current panel.');
        }

        return $this->plugin = $panel->getPlugin(ProfileFilamentPlugin::PLUGIN_ID);
    }

    /**
     * Retrieve the authenticated user's timezone. Fallback on UTC if none found.
     */
    public function userTimezone(?User $user = null): string
    {
        $user ??= auth()->user();

        $userTimezone = is_null(static::$findUserTimezoneUsingCallback)
            ? $user?->timezone
            : call_user_func(static::$findUserTimezoneUsingCallback, $user);

        return $userTimezone ?? 'UTC';
    }

    public function getVerifyEmailChangeUrl(MustVerifyEmail|Model|Authenticatable $user, string $newEmail, array $parameters = []): string
    {
        if (static::$createVerifyEmailChangeUrlCallback) {
            return call_user_func(static::$createVerifyEmailChangeUrlCallback, $user, $newEmail, $parameters);
        }

        return URL::temporarySignedRoute(
            Filament::getCurrentOrDefaultPanel()->generateRouteName('auth.email-change-verification.verify'),
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $user->getRouteKey(),
                'email' => Crypt::encryptString($newEmail),
                ...$parameters,
            ],
        );
    }

    public function getBlockEmailChangeVerificationUrl(MustVerifyEmail|Model|Authenticatable $user, string $newEmail, array $parameters = []): string
    {
        if (static::$createBlockEmailChangeUrlCallback) {
            return call_user_func(static::$createBlockEmailChangeUrlCallback, $user, $newEmail, $parameters);
        }

        return URL::temporarySignedRoute(
            Filament::getCurrentOrDefaultPanel()->generateRouteName('auth.email-change-verification.block-verification'),
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $user->getRouteKey(),
                'email' => Crypt::encryptString($newEmail),
                ...$parameters,
            ],
        );
    }

    public function getEmailVerificationUrl(MustVerifyEmail|Model|Authenticatable $user, array $parameters = []): string
    {
        if (static::$createEmailVerificationUrlCallback) {
            return call_user_func(static::$createEmailVerificationUrlCallback, $user, $parameters);
        }

        return URL::temporarySignedRoute(
            Filament::getCurrentOrDefaultPanel()->generateRouteName('auth.email-verification.verify'),
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $user->getRouteKey(),
                'hash' => hash('sha3-256', $user->getEmailForVerification()),
                ...$parameters,
            ],
        );
    }

    /**
     * Determine the initial multifactor authentication provider to show for a given user.
     *
     * @param  HasMultiFactorAuthentication&Authenticatable  $user
     */
    public function preferredMfaProviderFor(User $user, Collection $enabledProviders): ?string
    {
        // Use the user's preferred mfa provider or just use the first enabled provider if no preference is found.
        $preferredProvider = $user instanceof HasMultiFactorAuthentication
            ? $user->getPreferredMfaProvider()
            : null;

        if ($preferredProvider) {
            return $enabledProviders->firstWhere(
                fn (MultiFactorAuthenticationProvider $provider) => $provider->getId() === $preferredProvider,
            )?->getId() ?? $enabledProviders->first()?->getId();
        }

        return $enabledProviders->first()?->getId();
    }

    /**
     * Determine the initial sudo challenge provider to show for a given user.
     *
     * @param  User&HasMultiFactorAuthentication  $user
     */
    public function preferredSudoChallengeProviderFor(User $user, Collection $enabledProviders): ?string
    {
        // Use the user's preferred mfa provider or just use the first enabled provider if no preference is found.
        $preferredProvider = $user instanceof HasMultiFactorAuthentication
            ? $user->getPreferredMfaProvider()
            : null;

        if ($preferredProvider) {
            return $enabledProviders->firstWhere(
                fn (SudoChallengeProvider $provider) => $provider->getId() === $preferredProvider,
            )?->getId() ?? $enabledProviders->first()?->getId();
        }

        return $enabledProviders->first()?->getId();
    }

    /**
     * Generate a challenge token for requests that require a challenge.
     */
    public function challenge(int $length = 32): string
    {
        if (static::$generateChallengesUsingCallback) {
            return (static::$generateChallengesUsingCallback)();
        }

        return Random::token(length: $length);
    }

    public function generateWebauthnNonce(): string
    {
        $nonce = Str::random(32) . ':' . now()->unix();

        WebauthnSession::Nonce->put($nonce);

        return Crypt::encrypt($nonce);
    }

    public function verifyWebauthnNonce(?string $encryptedNonce): void
    {
        [$nonce, $timestamp] = explode(':', Crypt::decrypt($encryptedNonce), 2);
        [$storedNonce, $storedTimestamp] = explode(':', WebauthnSession::Nonce->pull(), 2);

        if (! hash_equals($storedNonce, $nonce)) {
            throw new RuntimeException('Invalid nonce');
        }

        if (! hash_equals($storedTimestamp, $timestamp)) {
            throw new RuntimeException('Invalid nonce timestamp');
        }

        if ((int) $storedTimestamp < now()->subMinutes(15)->unix()) {
            throw new RuntimeException('Nonce has expired');
        }
    }

    /**
     * Mostly for cross-domain webauthn, we need to ensure the response we're processing
     * is valid for the user.
     */
    public function verifyCrossDomainRequest(array $data, Authenticatable $user): bool
    {
        $storedChallenge = cache()->pull('mfa.external-challenge:' . $user->getKey());

        if (! Hash::check($storedChallenge, $data['challenge'])) {
            return false;
        }

        // Ensure the challenge is for the correct user.
        $decryptedUserId = (string) rescue(
            fn () => Crypt::decrypt($data['userId']),
            fn () => ''
        );

        if (! hash_equals((string) $user->getAuthIdentifier(), $decryptedUserId)) {
            return false;
        }

        if (Arr::has($data, 'nonce')) {
            try {
                $this->verifyWebauthnNonce($data['nonce']);
            } catch (Throwable) {
                return false;
            }
        }

        return true;
    }
}
