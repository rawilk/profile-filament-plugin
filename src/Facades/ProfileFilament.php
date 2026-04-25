<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Facades;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Rawilk\ProfileFilament\ProfileFilament as ProfileFilamentService;

/**
 * @method static string getBlockEmailChangeVerificationUrl(MustVerifyEmail|Model|User $user, string $newEmail, array $parameters = [])
 * @method static string getEmailVerificationUrl(MustVerifyEmail|Model|User $user, array $parameters = [])
 * @method static string getVerifyEmailChangeUrl(MustVerifyEmail|Model|User $user, string $newEmail, array $parameters = [])
 * @method static string preferredMfaProviderFor(User $user, Collection $enabledProviders)
 * @method static string preferredSudoChallengeProviderFor(User $user, Collection $enabledProviders)
 * @method static string userTimezone(User $user = null)
 *
 * @see ProfileFilamentService
 */
class ProfileFilament extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ProfileFilamentService::class;
    }
}
