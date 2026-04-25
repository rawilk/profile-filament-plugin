<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Providers;

use Illuminate\Auth\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Rawilk\ProfileFilament\Support\Config;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Config::getModel('authenticator_app'), Config::getPolicy('authenticator_app'));
        Gate::policy(Config::getModel('webauthn_key'), Config::getPolicy('webauthn_key'));
    }
}
