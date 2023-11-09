<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Providers;

use Illuminate\Auth\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(config('profile-filament.models.authenticator_app'), config('profile-filament.policies.authenticator_app'));
        Gate::policy(config('profile-filament.models.webauthn_key'), config('profile-filament.policies.webauthn_key'));
    }
}
