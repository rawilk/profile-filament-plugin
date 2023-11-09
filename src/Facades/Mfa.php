<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Facades;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool canUseAuthenticatorAppsForChallenge(User $user = null)
 * @method static bool canUseWebauthnForChallenge(User $user = null)
 * @method static User challengedUser()
 * @method static void confirmUserSession(User $user)
 * @method static bool isConfirmedInSession(User $user)
 * @method static bool hasChallengedUser()
 * @method static bool isValidRecoveryCode(string $code)
 * @method static bool isValidTotpCode(string $code)
 * @method static \Rawilk\ProfileFilament\Services\Mfa usingChallengedUser(?User $user)
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
