---
title: Sudo Mode
sort: 2
---

## Introduction

For sensitive actions, we can prompt for authentication before the action is performed, even when you're already signed in. For example, we consider the following actions sensitive because each action could allow a new person to access your account.

- Modification of your email address
- Addition or deletion of a passkey

Once a user authenticates to perform a sensitive action, their session is temporarily in "sudo mode". In sudo mode, additional sensitive actions can be performed without additional authentication prompts.

"sudo" is a reference to a program on Unix systems, where the name is short for "**su**peruser **do**". For more information, see [sudo](https://en.wikipedia.org/wiki/Sudo) on Wikipedia.

The package's sudo mode is loosely based off how GitHub handles sudo mode.

## Configuration

By default, sudo mode is set to expire after 2 hours of inactivity. If any sensitive action is performed within this time period, the timer is reset. You can change the duration of sudo mode in the config file:

```php
// config/profile-filament.php

'sudo' => [
    'expires' => DateInterval::createFromDateString('2 hours'),
],
```

## Authentication Methods

To confirm access with sudo mode, you can authenticate with your password. If the user has mfa enabled on their account, they can also use their authenticator apps or webauthn/passkeys to authenticate.

> {note} Recovery codes are not allowed to be used for sudo mode authentication.

### Preferred Challenge Mode

Like with [mfa](/docs/profile-filament-plugin/{version}/advanced-usage/mfa#user-content-preferred-mfa-method), you may specify a callback for determining which method should be initially shown to the user when sudo challenged. If no preferred method is found, we will default it to password authentication. If your callback for determining a user's preferred mfa method returns "recovery codes", we will force password authentication to be shown, since recovery codes can not be used for sudo challenges.

## Sudo Challenge

For any sensitive action required, a sudo challenge can be shown. It will appear as a modal which will prompt the user to verify their identity. Here is a screenshot of a challenge shown for a user with mfa enabled on their account:

![sudo challenge](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/sudo-challenge.png)

When this challenge is shown to the user, we will dispatch the `SudoModeChallenged` event.

### Use Sudo Challenge Action

To show a sudo challenge for your own sensitive actions, you need to do the following:

1. Use the `UsesSudoChallengeAction` trait on your livewire component:

```php
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Livewire\Component;

class YourComponent extends Component
{
    use UsesSudoChallengeAction;
}
```

2. Enforce sudo mode in the `mountUsing` function on your action.

```php
public function sensitiveAction(): Action
{
    return Action::make('sensitiveActionName')
        // ...
        ->mountUsing(function () {
            $this->ensureSudoIsActive(returnAction: 'sensitiveActionName');
        });
}
```

Make sure the `returnAction` parameter is the name of your action name, so it can be re-mounted by our action when sudo mode is entered.

You can optionally check that sudo mode is still active in your `action`, in the case the user entered sudo mode, but left the page idle long enough for sudo mode to expire.

```php
public function sensitiveAction(): Action
{
    return Action::make('sensitiveActionName')
        // ...
        ->action(function () {
            $this->ensureSudoIsActive(returnAction: 'sensitiveActionName');
        })
        ->mountUsing(function () {
            // ...
        });
}
```

## Middleware

To protect sensitive routes, you can apply the `RequiresSudoMode` middleware to your route. If sudo mode needs to be challenged, the middleware will redirect to a full-page sudo challenge. This challenge page looks and behaves exactly like the sudo challenge modal does.

```php
// routes/web.php

use Rawilk\ProfileFilament\Http\Middleware\RequiresSudoMode;

Route::get('/protected-route', 'YourController@index')->middleware([RequiresSudoMode::class]);
```

Like with the [mfa challenge](/docs/profile-filament-plugin/{version}/advanced-usage/mfa#user-content-mfa-challenge) page, you can specify a custom layout view if you need to in a service provider:

```php
use Rawilk\ProfileFilament\Filament\Pages\SudoChallenge;

public function boot(): void
{
    SudoChallenge::setLayout('your.layout');
}
```

## Disabling Sudo Mode

If you don't want to enforce sudo mode for sensitive actions performed by this package, you can disable it completely. This can be done using [Features](/docs/profile-filament-plugin/{version}/customizations/features) when registering the plugin.

```php
use Rawilk\ProfileFilament\Features;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->features(
            Features::defaults()->useSudoMode(false)
        )
)
```
