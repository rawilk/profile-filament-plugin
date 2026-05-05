---
title: Login
sort: 1
---

## Introduction

To utilize the [multi-factor authentication](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication) (MFA) features from this package, you will need to modify Filament's login page. Unlike how Filament handles MFA, I prefer to handle it on a separate challenge page instead of integrating it directly into the login form. The approach I take for the authentication process is very opinionated, however you are free to implement MFA differently if your application needs are different. 

## Modify the login page

To start you will need to override Filament's Login page with your own class. In your login page extension, you will need to override the `authenticate()` method. To make this simple, the package provides a `HandlesLoginForm` trait that you can use on your Login page. The trait sends the login request through Laravel's [Pipeline](https://laravel.com/docs/13.x/helpers#pipeline). We use some sensible defaults for this, however you can [override](#user-content-customize-authentication-process) the authentication process if necessary.

Here is the custom Login page to use in your panel:

```php
namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Rawilk\ProfileFilament\Auth\Login\Concerns\HandlesLoginForm;

class Login extends BaseLogin
{
    use HandlesLoginForm;
}
```

Now in your panel provider you can use your custom Login page:

```php
use Filament\PanelProvider;
use Filament\Panel;
use App\Filament\Pages\Auth\Login;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->login(Login::class)
            ->plugin(
                ProfileFilamentPlugin::make()
                    ->multiFactorAuthentication([
                        AppAuthenticationProvider::make(),
                    ])                
            )
    }
}
```

## Customize authentication process

Each of our authentication "pipes" handle a little bit of the authentication process. If you need to execute different logic, you can use the `sendLoginThrough()` method on the plugin and provide your own authentication pipeline.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use App\Actions\Auth\Login\AttemptToAuthenticate;

ProfileFilamentPlugin::make()
    // ...
    ->sendLoginThrough([
        AttemptToAuthenticate::class,
    ])
```

Your action classes should have either a `handle()` or `__invoke()` method for the pipeline to interact with. Here is a basic example to get you started.

```php
namespace App\Actions\Auth\Login;

use Closure;
use Rawilk\ProfileFilament\Auth\Login\Dto\LoginEventBagContract;

class AttemptToAuthenticate
{
    public function __invoke(LoginEventBagContract $request, Closure $next)
    {
        if (! auth()->attempt($request->getCredentialsFromFormData())) {
            // ...
        }
        
        $request->setUser(auth()->user());
    
        return $next($request);
    }
}
```

Here are the default login actions that we use to authenticate a user:

```php
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\ResolveUser;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\RedirectIfHasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\AttemptToAuthenticateUser;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\PrepareAuthenticatedSession;

$defaults = [
    ResolveUser::class,
    RedirectIfHasMultiFactorAuthentication::class,
    AttemptToAuthenticateUser::class,
    PrepareAuthenticatedSession::class,
];
```

## Auth attempt callback

To prevent unauthorized users from accessing your application, you may perform checks on that user before logging them in. A common use case for this would be to prevent banned users from signing in to the application. By default, our plugin only checks if the user can access the current filament panel.

To perform different authentication logic, you can use the `attemptAuthWith()` method on the plugin and provide either a closure or an array of closures to execute that will verify a given user is allowed to access the application.

If you're using the default [authentication pipes](#user-content-customize-authentication-process) from this package, we will execute this callback during the `ResolveUser` and `AttemptToAuthenticate` user pipes.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use App\Models\User;
use Filament\Models\Contracts\FilamentUser;
use Filament\Facades\Filament;

ProfileFilamentPlugin::make()
    // ...
    ->attemptAuthWith(function (User $user): bool {
        if ($user->isBanned()) {
            return false;
        }
        
        if (! ($user instanceof FilamentUser)) {
            return true;
        }
        
        return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
    })
```

> {tip} We will use this same callback during the [MFA](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication) process and during [Passkey login](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication#user-content-passkey-login) as well to verify a user is allowed to sign in.

> {note} When providing custom logic here we **will not** perform the `$user->canAccessPanel(...)` check anymore for you. It is up to you to include that condition in your custom logic.
