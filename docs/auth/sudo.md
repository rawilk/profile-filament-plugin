---
title: Sudo Mode
sort: 3
---

## Introduction

To maintain the security of your account when you perform a potentially sensitive action in your application, you can force your users to authenticate even though they're already signed in. For example, the following actions could be considered sensitive because each action could allow a new person or syste to access your account:

- Modification of a user's email address
- Addition (or deletion) of a new passkey or any other MFA provider

After you authenticate to perform a sensitive action, your session is temporarily in "sudo mode". In sudo mode, you can perform sensitive actions without additional authentication. By default, the plugin uses a two-hour session timeout period before prompting a user to authenticate again. During this time, any sensitive action that a user performs will reset the timer.

The package's implementation of sudo mode is based off how GitHub handles sudo mode.

## Enabling Sudo Mode

Sudo mode is enabled by default on the plugin. Similar to how we handle [multi-factor authentication](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication), sudo mode uses providers to handle different kinds of challenges for a user to verify their identity. We enable the [Password Provider](#user-content-password-provider) **only** by default; any multi-factor providers you enable on the plugin will need to have their respective sudo challenge providers enabled manually.

### Confirming access for sudo mode

To confirm access for sudo mode, a user can authenticate with their password. Optionally, you can enable any multi-factor providers to use a different authentication method, like a passkey or TOTP code.

The multi-factor providers offered by the plugin have a complementary `SudoChallengeProvider` counterpart. This allows you more control over which methods a user has available to use for sudo mode authentication in your application.

To add more sudo challenge providers, you can use pass an instance of them to the `sudoMode()` method on the plugin:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Sudo\Password\SudoPasswordProvider;
use Rawilk\ProfileFilament\Auth\Sudo\App\SudoAppAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Webauthn\SudoWebauthnProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Email\SudoEmailAuthenticationProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        // ...
    ])
    ->sudoMode([
        SudoAppAuthenticationProvider::make(),
        SudoWebauthnProvider::make(),
        SudoEmailAuthenticationProvider::make(),
        SudoPasswordProvider::make(),
    ])
```

One thing to note: Some options are available for both the `MultiFactorChallengeProvider` and their `SudoChallengeProvider` counterparts; however, you will need to change those options on both instances when you do modify any of the options.

> {tip} Don't forget to pass in an instance of the `SudoPasswordProvider` when adding in your other providers!

> {tip} Any custom sudo challenge providers you create should use the same `id` as their `MultiFactorChallengeProvider` counterpart (if applicable).

- [Confirming access with a password](#user-content-password-provider)
- [Confirming access with an authenticator app](#user-content-app-authentication-provider)
- [Confirming access with a passkey](#user-content-webauthn-provider)
- [Confirming access with an email code](#user-content-email-authentication-provider)

> {note} Recovery codes are not allowed to be used for a sudo challenge, so we have not created a recovery code provider for sudo mode.

## Password Provider

The sudo password provider is the only provider enabled by default and allows a user to authenticate with their password. We recommend always providing this provider even if you are using some of the MFA sudo providers.

![sudo password challenge](https://github.com/rawilk/profile-filament-plugin/blob/{branch}/assets/images/sudo/sudo-password.png?raw=true)

### Disabling when user has MFA enabled

By default, the password provider is always enabled for a user. However, you may wish to prevent a user from using their password to authenticate for sudo mode if they have MFA enabled on their account. You can use the `disableWhenUserHasMultiFactorAuthentication()` method on the password provider instance to disable it in that scenario.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Sudo\Password\SudoPasswordProvider;

ProfileFilamentPlugin::make()
    ->sudoMode([
        SudoPasswordProvider::make()
            ->disableWhenUserHasMultiFactorAuthentication()        
    ])
```

### Hide password reset link

If your panel has password reset enabled, we will show a password reset link on the sudo challenge too. To disable this feature, you can use the `hidePasswordResetLink()` method on the password provider instance.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Sudo\Password\SudoPasswordProvider;

ProfileFilamentPlugin::make()
    ->sudoMode([
        SudoPasswordProvider::make()
            ->hideResetPasswordLink()        
    ])
```

## App Authentication Provider

You must first have the [App Authentication](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication#user-content-app-authentication) provider enabled for the plugin. Then a user must register at least one authenticator app to their account first.

To enable this provider, you can add an instance of the `SudoAppAuthenticationProvider` to the `sudoMode()` method on the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Sudo\App\SudoAppAuthenticationProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        AppAuthenticationProvider::make(),
    ])
    ->sudoMode([
        SudoAppAuthenticationProvider::make(),
    ])
```

![sudo app challenge](https://github.com/rawilk/profile-filament-plugin/blob/{branch}/assets/images/sudo/sudo-app.png?raw=true)

### Changing the app code expiration time

You may change the expiration of the app codes like with the [app authentication mfa provider](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication#user-content-changing-the-app-code-expiration-time). 

To change the code window, for example to only be valid for 2 minutes after it is generated, you can use the `codeWindow()` method on the `SudoAppAuthenticationProvider` instance, set to `4`.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Sudo\App\SudoAppAuthenticationProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        AppAuthenticationProvider::make()
            ->codeWindow(4),
    ])
    ->sudoMode([
        SudoAppAuthenticationProvider::make()
            ->codeWindow(4),
    ])
```

## Webauthn Provider

You must first enable the [Webauthn Provider](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication#user-content-webauthn-authentication) on the plugin. Then, your user must register at least one security key to their account for this challenge to show up.

To enable this provider, you can add an instance of the `SudoWebauthnProvider` to the `sudoMode()` method on the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\WebauthnProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Webauthn\SudoWebauthnProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        WebauthnProvider::make(),
    ])
    ->sudoMode([
        SudoWebauthnProvider::make(),
    ])
```

![sudo webauthn challenge](https://github.com/rawilk/profile-filament-plugin/blob/{branch}/assets/images/sudo/sudo-webauthn.png?raw=true)

## Email Authentication Provider

You must first enable the [Email Authentication Provider](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication#user-content-email-authentication) on the plugin. Then, your user must enable email verification authentication on their account for this provider to show up.

To enable this provider, you can add an instance of the `SudoEmailAuthenticationProvider` to the `sudoMode()` method on the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\EmailAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Email\SudoEmailAuthenticationProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        EmailAuthenticationProvider::make(),
    ])
    ->sudoMode([
        SudoEmailAuthenticationProvider::make(),
    ])
```

![sudo email verification challenge](https://github.com/rawilk/profile-filament-plugin/blob/{branch}/assets/images/sudo/sudo-email.png?raw=true)

## Requiring sudo mode for actions

It's recommended to use a custom action class for simplicity; however, you can require sudo mode on [inline actions](#user-content-requiring-sudo-mode-for-inline-actions) too. 

Your action class will need to use the `RequiresSudoChallenge` trait, which will help with showing the sudo challenge when necessary. Here is a simple example of an action class that requires sudo mode.

> {note} The example shown here is only for actions that show a modal. For actions that execute immediately, a different trait will need to be used since it is handled slightly differently.

```php
use Filament\Actions\Action;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallenge;

class SensitiveAction extends Action
{
    use RequiresSudoChallenge;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->registerSudoChallenge();
        
        $this->modalHeading('My sensitive action');
        
        $this->schema([
            // ...
        ]);
        
        $this->action(function () {
            if ($this->shouldChallengeForSudo()) {
                $this->cancel();
            }
            
            // Perform sensitive action
        });
    }
}
```

The `registerSudoChallenge()` method on the trait will handle registering the sudo challenge as a child action on your action and it will handle checking for and showing the sudo challenge if necessary before the modal is shown.

The trait hooks into the `before()` and `mountUsing()` methods on the action, so if you need to execute code in either of those you can provide callbacks to the trait in your action class:

```php
use Closure;
use Filament\Actions\Action;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallenge;

class SensitiveAction extends Action
{
    use RequiresSudoChallenge;

    protected function setUp(): void
    {
        // ...
    }
    
    protected function getBeforeCallback(): ?Closure
    {
        return function () {
            // do something
        });
    }

    protected function getMountUsingCallback(): ?Closure
    {
        return function () {
            // do something
        });
    }
}
```

### Requiring sudo mode for non-modal actions

For actions that don't show a modal at all you can use the `RequiresSudoChallengeWithoutModal` trait.

```php
use Filament\Actions\Action;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallengeWithoutModal;

class SensitiveAction extends Action
{
    use RequiresSudoChallengeWithoutModal;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->registerSudoChallenge();
        
        $this->action(function () {
            if ($this->shouldChallengeForSudo()) {
                $this->cancel();
            }
            
            // do sensitive action
        });
    }
}
```

Like with the modal variant above, the `registerSudoChallenge()` handles registering a `mountUsing()` callback on the action to check for and enfore sudo mode for the action. You're not done yet with this variant, however. After creating your action, you will need to ensure your livewire component has the `SudoChallengeAction` registered and available on it, and that Filament's modal blade component is in the component.

To make this easy, we've provided a `NeedsSudoChallengeAction` trait for these components:

```php
use Livewire\Component;
use App\Filament\Actions\SensitiveAction;
use Rawilk\ProfileFilament\Auth\Sudo\Livewire\Concerns\NeedsSudoChallengeAction;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;

class YourLivewireComponent extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use NeedsSudoChallengeAction;
    
    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->content }}
            
            <x-filament-actions::modals />
        </div>
        HTML;
    }
    
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                SensitiveAction::make('sensitive-action'),
            ]);
    }
}
```

> {note} We're using a plain livewire component in this example, but the same concept should apply with Filament pages too.

### Requiring sudo mode for inline actions

If you prefer to not make an action class and just render the action in your schema via `Action::make()`, you can still use sudo mode to protect the action; it will just take a bit more work on your end for it.

You basically will need to do what we are doing in the `RequiresSudoChallenge` trait in your action.

```php
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Livewire\Component;
use Rawilk\ProfileFilament\Auth\Sudo\Concerns\InteractsWithSudo;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\SudoChallengeAction;
use Rawilk\ProfileFilament\Auth\Sudo\Events\SudoModeChallengeWasPresented;
use Illuminate\Http\Request;
use Filament\Actions\Contracts\HasActions;

class YourLivewireComponent extends Component
{
    use InteractsWithSudo;
    
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Action::make('sensitive-action')
                    ->registerModalActions([
                        SudoChallengeAction::make(),
                    ])
                    ->before(function (HasActions $livewire, Request $request) {
                        if (! $this->shouldChallengeForSudo()) {
                            return;
                        }
                        
                        SudoModeChallengeWasPresented::dispatch(auth()->user(), $request);
                        
                        $livewire->mountAction('sudoChallenge');
                    })
                    ->mountUsing(function (HasActions $livewire, Request $request) {
                        if (! $this->shouldChallengeForSudo()) {
                            $this->extendSudo(); 
                        
                            return;
                        }
                        
                        SudoModeChallengeWasPresented::dispatch(auth()->user(), $request);
                        
                        $livewire->mountAction('sudoChallenge');
                    })
                    ->schema([
                        // ...
                    ])
                    ->action(function (Action $action) {
                        if ($this->shouldChallengeForSudo()) {
                            $action->cancel();
                        }
                        
                        // Do sensitive action
                    }),
            ]);
    }
}
```

If you're not using a modal for the action, you will need to perform similar steps to what the `RequiresSudoChallengeWithoutModal` trait is doing.

> {tip} You don't have to use the `InteractsWithSudo` trait, however it makes checking for sudo mode a little easier to do.

## Protect routes with sudo mode

In addition to requiring authentication for sensitive filament actions in your application, you can require sudo mode for sensitive routes as well by using the `RequiresSudoMode` middleware on a route. Similar to the sudo challenge modal, a full-page `SudoChallenge` will be presented if sudo mode is not currently active.

```php
use Illuminate\Routing\Route;
use Rawilk\ProfileFilament\Auth\Sudo\Http\Middleware\RequiresSudoMode;

Route::get('/admin/sensitive-route', fn () => 'ok')
    ->middleware(['auth', RequiresSudoMode::class]);
```

> {note} If your sensitive route is not associated with a panel, the default panel will be used in the middleware.

## Disabling Sudo Mode

If you don't want to use the plugin's sudo mode for sensitive actions, you may disable sudo mode by passing a boolean `false` to the `sudoMode()` method on the plugin:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->sudoMode(false)
```
