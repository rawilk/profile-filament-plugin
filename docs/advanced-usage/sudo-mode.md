---
title: Sudo Mode
sort: 2
---

## Introduction

For sensitive actions, we can prompt for authentication before the action is performed, even when you're already signed in. For example, we consider the following actions sensitive because each action could allow a new person to access your account.

-   Modification of your email address
-   Addition or deletion of a passkey

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

![sudo challenge](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/sudo-challenge.png?raw=true)

When this challenge is shown to the user, we will dispatch the `SudoModeChallenged` event.

### Use Sudo Challenge Action

To show a sudo challenge for your own sensitive actions, you have two options: create a custom action class, or include a trait in a livewire component to enforce sudo mode for you. Below is a basic overview of how to implement each strategy:

#### Custom Action Class

With this strategy, a custom filament action will check for and enforce sudo mode before its action is executed. This is typically what you'll want to reach for when requiring sudo mode for actions.

1. First, create a new filament action and pull the `RequiresSudo` trait on the class. Here is a basic example of what you'll need to include inside of your action's `setup` method:

```php
use Filament\Actions\Action;
use Livewire\Component;

class SensitiveAction extends Action
{
    use RequiresSudo;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->before(function (Component $livewire) {
            $this->ensureSudoIsActive($livewire);
        });
        
        $this->mountUsing(function (Component $livewire) {
            $this->mountSudoAction($livewire);
        });
        
        $this->registerModalActions([
            $this->getSudoChallengeAction(),
        ]);
    }
}
```

> {tip} You should only need to include the `before` hook if your action requires confirmation. This is to ensure sudo mode is still active in the event of the user entering sudo mode but waiting too long to perform the action.

2. With the sensitive action defined, you just need to define and use it like you normally would in your livewire component:

```php
use Filament\Actions\Action;
use Livewire\Component;

class YourComponent extends Component
{
    // ...
    
    public function sensitiveAction(): Action
    {
        return SensitiveAction::make();
    }
}
```

> {tip} This will also work with infolist and table actions as well. The `RequiresSudo` trait will handle everything for you.

#### From Livewire Component

In cases where the user needs to perform a sensitive action but a filament action class doesn't make sense, you may check for and enforce sudo mode directly from your livewire component.

1. Include the `SudoChallengeForm` livewire component in your component's markup.

```html
<div>
    <!-- your component markup -->
    
    @livewire(\Rawilk\ProfileFilament\Livewire\Sudo\SudoChallengeForm::class)
</div>
```

> {note} If you have multiple components on the same page that will check for sudo mode this way, you should include our livewire component on the page itself, outside your livewire component definitions.

2. Use the `UsesSudoChallengeAction` trait on your livewire component:

```php
use Livewire\Component;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;

class YourComponent extends Component
{
    use UsesSudoChallengeAction;
}
```

3. Enforce sudo mode by calling `$this->ensureSudoIsActive()` in your action method. You should also listen for the `sudo-active` livewire event on your method as well to continue processing once the user has entered sudo mode.

```php
use Livewire\Attributes\On;

#[On('sudo-active')]
public function sensitiveAction(): void
{
    if (! $this->ensureSudoIsActive()) {
        return;
    }

    // Sudo is active, continue processing.
}
```

The `ensureSudoIsActive` method in our trait will dispatch an event to our `SudoChallengeForm`, which will take care of enforcing sudo mode for you. Once sudo mode has been entered, our component will dispatch the `sudo-active` event, which you should listen to as shown above. 

If you have multiple sensitive actions in the same component, you can pass the method name as an argument to `ensureSudoIsActive`, which will then be included in the payload of the `sudo-active` event once it is dispatched.

```php
$this->ensureSudoIsActive(method: 'mySensitiveAction');
```

If you have data that you will need to access across requests, you can include that as an argument to the `ensureSudoIsActive` method as well.

```php
$this->ensureSudoIsActive(data: ['foo' => 'bar']);
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
