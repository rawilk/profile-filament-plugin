<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Facades;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static User challengedUser()
 * @method static void confirmUserSession(User $user)
 * @method static bool isConfirmedInSession(User $user)
 * @method static void pushChallengedUser(User $user, bool $remember = false)
 * @method static void flushPendingSession()
 * @method static bool passwordConfirmationHasExpired()
 *
 * @see \Rawilk\ProfileFilament\Services\Mfa
 */
class Mfa extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Rawilk\ProfileFilament\Services\Mfa::class;
    }
}
