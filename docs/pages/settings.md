---
title: Settings
sort: 2
---

## Introduction

The Settings page is meant to act as an account settings page for your users to modify certain account settings that aren't necessarily public. It's also for account admin functions, such as deleting your account.

Here is what the account settings page will look like by default:

![account settings](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/pages/account-settings.png?raw=true)

## Default livewire components

The account settings page consists of Livewire components that provide the page's functionality. You can [extend, replace or remove](/docs/profile-filament-plugin/{version}/configuration/pages#user-content-livewire-components) any of the components on this page.

The default Livewire components rendered onto the account settings page include:

- `Rawilk\ProfileFilament\Livewire\Emails\UserEmail`
- `Rawilk\ProfileFilament\Livewire\DeleteAccount`

## Email address

This Livewire component is responsible for displaying the authenticated user's email and providing a form to edit it.

If you have [Sudo Mode](/docs/profile-filament-plugin/{version}/auth/sudo) enabled, we will require the user to verify their identity before they are allowed to edit their email address.

By default, we will update the user's email address without any kind of verification from the user.

### Require email change verification

For added account security, you can require users to verify their new email address before it's actually updated on their account. This is done by sending a verification email to the new address, which contains a link that the user must click to verify their new email address. The email address in the database is not updated until the user clicks the link in the email.

The link that a user is sent is valid for 60 minutes. At the same time as the email to the new address is sent, an email to the old address is also sent, with a link to block the change. This is a security feature to potentially prevent a user from being affected by a malicious actor.

> {tip} This feature can (and should) be used alongside the `MustVerifyEmail` contract provided by Laravel.

To start, you need to enable email change verification on your panel:

```php
use Filament\Panel;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->emailChangeVerification()
        ->plugin(
            ProfileFilamentPlugin::make()
        );
}
```

> {note} While we are enabling email change verification on the panel, we are completely overriding the default behavior with the plugin.

Next, you need to ensure the `create_pending_user_emails_table` [migration](/docs/profile-filament-plugin/{version}/installation#user-content-migrations). Here is the content of the migration if you don't publish the package's migrations.

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Rawilk\ProfileFilament\Support\Config;

Schema::create(Config::getTableName('pending_user_email'), function (Blueprint $table) {
    $table->id();

    $table->morphs('user');
    $table->string('email')->index();
    $table->string('token');

    $table->timestamp('created_at')->nullable();
});
```

### Require email verification only

If you are only requiring initial email verification, we will still send an email verification notification to the new address, however, we will update the email address in the database right away. There is also no security feature to block the email address change.

For this feature you only need to implement the `MustVerifyEmail` trait on your user model and make sure that you have an `email_verified_at` column on your `users` database table.

```php
use Illuminate\Foundation\Auth\User as BaseUser;
use Illuminate\Auth\MustVerifyEmail;

class User extends BaseUser implements MustVerifyEmail
{
    // ...
}
```

> {note} If you have email verification required on the filament panel (`$panel->emailVerification()`) and the user updates their email address, we will reload the page so they are forced to see the email verification prompt until they verify their new email.

## Delete Account

This Livewire component is responsible for deleting the authenticated user's account and then logging them out. We've kept this component very basic by default, but it can be customized to meet your application's needs.

This is a sensitive action, so [Sudo Mode](/docs/profile-filament-plugin/{version}/auth/sudo) is required if you have it enabled on the plugin. We will also require the user to enter their email address as a confirmation that they are truly sure they want to delete their account.

Here is a screenshot of the prompt once you've entered sudo mode:

![delete account confirmation](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/actions/delete-account.png?raw=true)

### Customize account deletion logic

For a lot of applications you will probably need to customize the logic for deleting a user's account. We use an [action](/docs/profile-filament-plugin/{version}/configuration/actions) class for handling the account deletion process.

We've kept it very basic by default, but you can create your own action class to handle the deletion logic:

```php
namespace App\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Contracts\DeleteAccountAction as DeleteAccountContract;

class DeleteAccountAction implements DeleteAccountContract
{
    public function __invoke(User $user)
    {
        // $user->delete();
    }
}
```

> {tip} You don't need to worry about logging the user out with the action; the Filament delete user account action will handle that for you.

> {note} Your action must either extend ours or implement the `DeleteAccountAction` interface for the livewire component to call it when deleting the user's account.

After you've defined your action class, just add it to the `profile-filament` config:

```php
'actions' => [
    'delete_account' => App\Actions\DeleteAccountAction::class,
    // ...
],
```
