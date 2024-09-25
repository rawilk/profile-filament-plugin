<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Concerns\TwoFactorAuthenticatable;
use Rawilk\ProfileFilament\Events\TwoFactorAuthenticationChallenged;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RequiresTwoFactorAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (filament()->auth()->guest()) {
            return $next($request);
        }

        $user = filament()->auth()->user();
        throw_unless(
            in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user), true),
            new RuntimeException('User class [' . $user::class . '] must use the trait ' . TwoFactorAuthenticatable::class),
        );

        if (! $this->userHasMfaEnabled($user)) {
            return $next($request);
        }

        if (! $this->shouldCheckForMfa($request, $user)) {
            return $next($request);
        }

        if (Mfa::isConfirmedInSession($user)) {
            return $next($request);
        }

        TwoFactorAuthenticationChallenged::dispatch($user);

        return redirect()->guest($this->getRedirectUrl($request));
    }

    /**
     * Some routes, like logout, should always be accessible.
     */
    protected function shouldCheckForMfa(Request $request, User $user): bool
    {
        if (Str::of($request->route()?->getName() ?? '')->contains('logout')) {
            return false;
        }

        /**
         * An application may have some edge cases where mfa shouldn't be checked for, such as
         * during user impersonation.
         */
        return ProfileFilament::shouldCheckForMfa($request, $user);
    }

    protected function userHasMfaEnabled(User $user): bool
    {
        return $user->two_factor_enabled === true;
    }

    protected function getRedirectUrl(Request $request): string
    {
        $panelId = filament()->getCurrentPanel()?->getId();

        if (filament()->hasTenancy() && $tenantId = $request->route()?->parameter('tenant')) {
            return route("filament.{$panelId}.auth.mfa.challenge", ['tenant' => $tenantId]);
        }

        return route("filament.{$panelId}.auth.mfa.challenge");
    }
}
