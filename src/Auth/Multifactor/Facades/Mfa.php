<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Facades;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Facade;
use Rawilk\ProfileFilament\Auth\Multifactor\Services\Mfa as MfaService;

/**
 * @method static User challengedUser()
 * @method static void confirmUserSession(User $user)
 * @method static bool isConfirmedInSession(User $user)
 * @method static void pushChallengedUser(User $user, bool $remember = false)
 * @method static void flushPendingSession()
 * @method static bool passwordConfirmationHasExpired()
 *
 * @see MfaService
 */
class Mfa extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MfaService::class;
    }
}
