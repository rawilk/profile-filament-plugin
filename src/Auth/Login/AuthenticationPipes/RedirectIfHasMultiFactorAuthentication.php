<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Auth\Login\Dto\LoginEventBagContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Contracts\Responses\MultiFactorChallengeResponse;
use Rawilk\ProfileFilament\Facades\Mfa;

class RedirectIfHasMultiFactorAuthentication
{
    public function __invoke(LoginEventBagContract $request, Closure $next)
    {
        if ($this->userHasMultiFactorEnabled($request->user())) {
            Mfa::pushChallengedUser(
                user: $request->user(),
                remember: $request->shouldRememberUser(),
            );

            return app(MultiFactorChallengeResponse::class);
        }

        return $next($request);
    }

    protected function userHasMultiFactorEnabled(Authenticatable $user): bool
    {
        if (! $user instanceof HasMultiFactorAuthentication) {
            return false;
        }

        return $user->hasMultiFactorAuthenticationEnabled();
    }
}
