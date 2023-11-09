<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Facades;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array getMfaAuthenticationPipes()
 * @method static string preferredMfaMethodFor(User $user, array $availableMethods)
 * @method static string preferredSudoChallengeMethodFor(User $user, array $availableMethods)
 * @method static bool shouldCheckForMfa(Request $request, User $user)
 * @method static bool shouldShowProfileSection(string $section)
 * @method static string userTimezone(\Illuminate\Contracts\Auth\Authenticatable $user = null)
 */
class ProfileFilament extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Rawilk\ProfileFilament\ProfileFilament::class;
    }
}
