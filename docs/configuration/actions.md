---
title: Actions
sort: 3
---

## Introduction

Not to be confused with the Filament actions in the package, some functionality of this package is implemented in action classes. You can override the default behavior by creating your own action classes and registering them in the `config/profile-filament.php` config file.

## Overriding actions

Here is an example where we override the `UpdatePasswordAction` to add custom logic after the password for a user is updated. We are extending the default action class in this example, but you don't have to as long as you implement the default action class's interface, which is `Rawilk\ProfileFilament\Contracts\UpdatePasswordAction` in this case.

First, let's create the custom action class:

```php
namespace App\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Actions\UpdatePasswordAction;

class CustomUpdatePasswordAction extends UpdatePasswordAction
{
    public function __invoke(User $user, string $newPassword)
    {
        // Call the parent method to update the password
        parent::__invoke($user, $newPassword);

        // Add your custom logic here
    }
}
```

Next, register the custom action in the `config/profile-filament.php` config file:

```php
// config/profile-filament.php

return [
    // ...
    'actions' => [
        // ...
        'update_password' => App\Actions\CustomUpdatePasswordAction::class,
    ],
];
```

## Overriding webauthn actions

The actions related to [WebAuthn](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication#user-content-webauthn-authentication) are stored in the `webauthn.actions` config key instead of the `actions` key in the `config/profile-filament.php` config file.

[Overriding](#user-content-overriding-actions) them requires the same process as the other actions in the package; once you've created your custom webauthn action you can place it in the `webauthn.actions` config key for the relevant action you're overriding:

```php
// config/profile-filament.php

return [
    // ...
    'webauthn' => [
        'actions' => [
            'store_security_key' => App\Actions\CustomStoreSecurityKeyAction::class,
        ],
    ],
];
```
