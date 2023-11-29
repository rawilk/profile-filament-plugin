---
title: Settings
sort: 2
---

## Introduction

The Settings page is meant to act as an account settings page for your users to modify certain account settings that aren't necessarily public. It's also for account admin functions, such as deleting your account.

Each of the components on this page can be customized and swapped out for your own implementations. See [Swap Components](/docs/profile-filament-plugin/{version}/customizations/page-customization#user-content-swap-components) for more information on how to do that.

Here is what the account settings page will look like by default:

![settings page](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/settings-page.png)

## Email address

This component is responsible for displaying the authenticated user's email address, and providing a form to edit it. If you have the `MustVerifyNewEmail` contract on your user model, this component will also show a pending email change for the authenticated user as well. See the [Pending Email Verification](/docs/profile-filament-plugin/{version}/installation#user-content-pending-email-verification) installation docs for more information on configuring this.

If you have [Sudo Mode](/docs/profile-filament-plugin/{version}/advanced-usage/sudo-mode) enabled, we will require the user to verify their identity before they are allowed to edit their email address.

If you want to completely remove this form from the page, please see [Features](/docs/profile-filament-plugin/{version}/customizations/features#user-content-update-email) for more information on how to do that.

Here is what the form looks like if you require users to verify new email addresses:

![edit email form](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/edit-email-form.png)

When a user submits the edit email form, we will send an email to the new email address with a verification link, which will be a temporary signed url. This link is set to expire in 60 minutes of it being sent. The value for this expiration is determined by the `auth.verification.expire` configuration value. You can modify this in your `config/auth.php` file.

If you need to customize the email that is sent, you may modify either the language lines that are used (`mail.php` language file) in the mailable, or modify the mailable itself. Please see the [Pending Email Verification](/docs/profile-filament-plugin/{version}/advanced-usage/mailables#user-content-pending-email-verification) docs for more information on customizing this email.

When there is a pending email change for the user, we will show that in the UI, along with actions to either resend the link or cancel the change. Here is a screenshot of what that looks like by default:

![pending email change](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/pending-email-change.png)

> {tip} Like the edit email form, the cancel email change action requires sudo mode verification first, if you have sudo mode enabled.

### Other situations

If you don't have `MustVerifyNewEmail` added on your User model, but have Laravel's `MustVerifyEmail` contract on your user model, we will update the user's email address immediately, but also invalidate their email verification status and send a new email verification notification to the user.

If you don't have either of those contracts on your user model, we will just update the user's email address immediately and do nothing else.

## Delete Account

This component is responsible for deleting the authenticated user's account, and then logging them out. We've kept this component very basic by default, but it can easily be customized to meet your application's needs.

If you want to completely remove this form from the page, please see [Features](/docs/profile-filament-plugin/{version}/customizations/features#user-content-delete-account) for more information on how to do that.

In addition to [Sudo Mode](/docs/profile-filament-plugin/{version}/advanced-usage/sudo-mode), we also require the user to enter their email address in as a confirmation that they are truly sure they want to delete their account. If you've ever deleted a repository on GitHub, this will seem familiar. Here is a screenshot of what this confirmation form will look like:

![delete account confirmation](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/delete-account-confirm.png)

### Customization

Here are some of the ways you can customize the delete account process in your application.

#### Translations

If you just need to adjust some verbiage, [publishing](/docs/profile-filament-plugin/{version}/installation#user-content-translations) and modifying the language lines are the way to go. Most of the language lines can be found in the `pages/settings.php` language file.

#### View

For some changes, overriding our package's view may be a viable solution. Be sure to [publish the package views](/docs/profile-filament-plugin/{version}/installation#user-content-views), and then override the `livewire/delete-account.blade.php` view. 

#### Action

Sometimes, you may just need to override how the user account is deleted. To do this, you just need to override the delete account action class, and then add your custom class to the config file.

```php
namespace App\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Rawilk\ProfileFilament\Actions\DeleteAccountAction;

class CustomDeleteAccountAction extends DeleteAccountAction
{
    public function __invoke(User $user)
    {
        $user->delete();
    
        // ...    
    }
}
```

Now you just need to add your custom action class to the config file:

```php
// config/profile-filament.php

'actions' => [
    'delete_account' => \App\Actions\DeleteAccountAction::class,
    // ...
],
```

#### Swap Component

For the most control and customization, you can implement your own livewire component:

```php
namespace App\Livewire;

use Rawilk\ProfileFilament\Livewire\DeleteAccount as BaseDeleteAccount;

class DeleteAccount extends BaseDeleteAccount
{
    // ...
}
```

```php
use App\Livewire\DeleteAccount;
use Rawilk\ProfileFilament\Filament\Pages\Settings;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Livewire\DeleteAccount as BaseDeleteAccount;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->swapComponent(
            page: Settings::class,
            component: BaseDeleteAccount::class,
            newComponent: DeleteAccount::class,
        )
)
```
