---
title: Notifications
sort: 2
---

## Introduction

There are a few notifications that get sent out by the package for certain actions, such as when a user changes their email address. Most of them have a `$toMailCallback` static property that you can set in a service provider to customize the notification's email content.

## Customizing notification content

The following notifications sent out by this package can have their email message configured with a callback function:

- `Rawilk\ProfileFilament\Notifications\Emails\NoticeOfEmailChangeRequest` - sends to the user's current email address with a block verification url if the plugin and panel have [email change verification](/docs/profile-filament-plugin/{version}/pages/settings#user-content-require-email-change-verification) enabled
- `Rawilk\ProfileFilament\Notifications\Emails\VerifyEmailChange` - sends to the email address a user is attempting to change their email address to if the plugin and panel have [email change verification](/docs/profile-filament-plugin/{version}/pages/settings#user-content-require-email-change-verification) enabled

With these notifications, you can change the entire message returned from the notification's `toMail` method in a service provider:

```php
use Illuminate\Support\ServiceProvider;
use Rawilk\ProfileFilament\Notifications\Emails\VerifyEmailChange;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        VerifyEmailChange::$toMailCallback = function ($notifiable, $verificationUrl, $newEmail) {
            return (new MailMessage)
                ->line('Your content here');
        };
    }
}
```

If you just want to change the verbiage a little bit, you could modify the language lines for each of the notifications instead.

### Customizing notifications with a custom class instead

If you'd rather define your own notification class, you can do that as well and just [re-bind](https://laravel.com/docs/13.x/container#binding) the notification you're replacing to the container in a service provider.

First, create your custom notification:

```php
namespace App\Notifications;

use Rawilk\ProfileFilament\Notifications\Emails\VerifyEmailChange;

class CustomVerifyEmailChange extends VerifyEmailChange
{
    // ...
}
```

Now you can re-bind the notification class in the service provider:

```php
use App\Notifications\CustomVerifyEmailChange;
use Rawilk\ProfileFilament\Notifications\Emails\VerifyEmailChange;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->bind(
            VerifyEmailChange::class,
            fn ($app) => $app->make(CustomVerifyEmailChange::class),
        );
    }
}
```

## Other notifications

The following notifications are sent out by the package, but can be configured in other ways:

- `Rawilk\ProfileFilament\Auth\Multifactor\Email\Notifications\VerifyEmailAuthenticationNotification` - sends to the user when they need to verify their identity for MFA or sudo with the [email authentication provider](/docs/profile-filament-plugin/auth/multi-factor-authentication#user-content-changing-the-notification)
