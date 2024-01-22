<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Http\Request;
use Rawilk\ProfileFilament\Actions\Auth\PrepareUserSession;
use Rawilk\ProfileFilament\Enums\Livewire\MfaChallengeMode;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;

class ProfileFilament
{
    /**
     * The callback that is responsible for finding the authenticated user's timezone.
     *
     * @var callable|null
     */
    public static $findUserTimezoneUsingCallback;

    /**
     * The callback that is responsible for determining if mfa should
     * be enforced by our middleware.
     *
     * @var callable|null
     */
    public static $shouldCheckForMfaCallback;

    /**
     * The callback that is responsible for determining a user's preferred
     * mfa method.
     *
     * @var callable|null
     */
    public static $getPreferredMfaMethodCallback;

    /**
     * The callback that is responsible for determining the pipes
     * to be called for a two-factor challenge.
     *
     * @var callable|null
     */
    public static $mfaAuthenticationPipelineCallback;

    /**
     * Register a callback that is responsible for retrieving the authenticated user's timezone.
     */
    public static function findUserTimezoneUsing(callable $callback): void
    {
        static::$findUserTimezoneUsingCallback = $callback;
    }

    /**
     * Register a callback that is responsible for determining if our middleware
     * should enforce mfa.
     */
    public static function shouldCheckForMfaUsing(callable $callback): void
    {
        static::$shouldCheckForMfaCallback = $callback;
    }

    /**
     * Register a callback that is responsible for determining a user's preferred mfa method.
     */
    public static function getPreferredMfaMethodUsing(callable $callback): void
    {
        static::$getPreferredMfaMethodCallback = $callback;
    }

    /**
     * Register a callback that is responsible for determining the pipes to send
     * a two-factor authentication challenge through.
     */
    public static function mfaAuthenticationPipelineUsing(callable $callback): void
    {
        static::$mfaAuthenticationPipelineCallback = $callback;
    }

    /**
     * Retrieve the authenticated user's timezone. Fallback on UTC if none found.
     */
    public function userTimezone(?User $user = null): string
    {
        $user ??= auth()->user();

        $userTimezone = is_null(static::$findUserTimezoneUsingCallback)
            ? $user?->timezone /** @phpstan-ignore-line */
            : call_user_func(static::$findUserTimezoneUsingCallback, $user);

        return $userTimezone ?? 'UTC';
    }

    /**
     * Determine if mfa should be enforced for a given request and user. The most common
     * use case for this is when user impersonation is being used in an application.
     */
    public function shouldCheckForMfa(Request $request, User $user): bool
    {
        if (is_callable(static::$shouldCheckForMfaCallback)) {
            return call_user_func(static::$shouldCheckForMfaCallback, $request, $user);
        }

        return true;
    }

    /**
     * Retrieve the preferred mfa method for a given user.
     */
    public function preferredMfaMethodFor(User $user, array $availableMethods): string
    {
        if (is_callable(static::$getPreferredMfaMethodCallback)) {
            return call_user_func(static::$getPreferredMfaMethodCallback, $user, $availableMethods, false);
        }

        // By default, return the first mfa method we find.
        if (in_array(MfaChallengeMode::App->value, $availableMethods, true)) {
            return MfaChallengeMode::App->value;
        }

        if (in_array(MfaChallengeMode::Webauthn->value, $availableMethods, true)) {
            return MfaChallengeMode::Webauthn->value;
        }

        return MfaChallengeMode::RecoveryCode->value;
    }

    public function preferredSudoChallengeMethodFor(User $user, array $availableMethods): string
    {
        /** @phpstan-ignore-next-line */
        if (! $user->two_factor_enabled) {
            return SudoChallengeMode::Password->value;
        }

        $preferredMethod = is_callable(static::$getPreferredMfaMethodCallback)
            ? call_user_func(static::$getPreferredMfaMethodCallback, $user, $availableMethods, true)
            : SudoChallengeMode::Password->value;

        // Recovery codes cannot be used for a sudo challenge.
        if ($preferredMethod === MfaChallengeMode::RecoveryCode->value) {
            return SudoChallengeMode::Password->value;
        }

        return $preferredMethod;
    }

    /**
     * Get the pipes to send a two-factor authentication challenge request through.
     *
     * Note: This does not apply to the middleware, as we are already authenticated
     * into the system at this point.
     */
    public function getMfaAuthenticationPipes(): array
    {
        if (is_callable(static::$mfaAuthenticationPipelineCallback)) {
            return call_user_func(static::$mfaAuthenticationPipelineCallback);
        }

        return [
            PrepareUserSession::class,
        ];
    }
}
