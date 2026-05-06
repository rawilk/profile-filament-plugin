---
title: Security
sort: 3
---

## Introduction

The Security profile page provides a UI for a user to update their password, as well as managing a user's available multi-factor authentication methods.

Here is what the security page can look like with the default update password form and a user that has all the multi-factor authentication providers supported by this package enabled on their account:

![security page](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/pages/security.png?raw=true)

## Default livewire components

The security settings page consists of Livewire components that provide the page's functionality. You can [extend, replace, or remove](/docs/profile-filament-plugin/{version}/configuration/pages#user-content-livewire-components) any of the components on this page.

The default Livewire components rendered onto the security settings page include:

- `Rawilk\ProfileFilament\Livewire\UpdatePassword`
- `Rawilk\ProfileFilament\Auth\Multifactor\Livewire\MultiFactorAuthenticationManager`

## Update password

The Update Password component provides a simple form for your users to update their current password. By default, we require a user to confirm their current password, as well as re-entering the new password as a confirmation. Both of these fields can be removed from the form if desired.

### Password hashing

By default, we assume your user model is using the `hashed` attribute cast on your user's password field, so we will not hash the new password for you. If you're not using the hashed attribute cast, you will need to set the `hash_user_passwords` in the `profile-filament` config file to `true`:

```php
// config/profile-filament.php

'hash_user_passwords' => true,
```

### Disabling password confirmation

If you don't want users to re-enter their new password as a confirmation, you can disable the field. Pass in a boolean `false` to the `requirePasswordConfirmation()` method on the plugin to remove this field:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->requirePasswordConfirmation(false)
```

### Disabling the current password field

It's recommended to leave this field enabled as a security measure, but you can remove the current password field from the update password form by passing a boolean `false` to the `requireCurrentPassword()` method on the plugin:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->requireCurrentPassword(false)
```

### Hiding the reset password link

If your panel has the reset password feature enabled, we will show a link to reset the user's password on this form. If you'd rather not show the link here, you can hide it by passing a boolean `false` to the `showPasswordResetLinkInUpdatePasswordForm()` on the plugin:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->showPasswordResetLinkInUpdatePasswordForm(false)
```

If you decide to keep the link in the form, be sure to override the `mount()` method in Filament's request password reset page to remove the redirect if a user is authenticated.

## Multi-factor authentication

The multi-factor authentication (MFA) manager provides a UI for managing a user's second factors for authentication. Once a user enables MFA for the first time, a set of recovery codes will be generated for the user if they ever lose access to their second factors.

Any [MFA provider](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication) you have enabled on the plugin in the panel will show up in the MFA manager component. Almost all the customization you can do is done in each of the MFA provider instances, since they each manage their own management schemas.

### Preferred mfa provider

The MFA manager also provides a simple UI for allowing a user to select their preferred MFA provider. Once a user has two or more providers enabled, we will show a select field that allows them to choose their preferred provider when authenticating. The preferred MFA provider selected will be the initial provider challenge shown during MFA and Sudo challenges.

If you follow the steps to set up [MFA](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication), you should already have your user model set up to handle this feature. By default, our `InteractsWithMultiFactorAuthentication` trait will look for a nullable string column `preferred_mfa_provider` on your user model. You will need to override the trait methods if you want to store the user's preferrence differently:

```php
use Illuminate\Foundation\Auth\User as BaseUser;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Concerns\InteractsWithMultiFactorAuthentication;

class User extends BaseUser implements HasMultiFactorAuthentication
{
    use InteractsWithMultiFactorAuthentication;

    public function getPreferredMfaProvider(): ?string
    {
        // return user's stored preference
    }

    public function setPreferredMfaProvider(?string $provider): void
    {
        // update user's stored preference
    }
}
```

## Page configuration

In addition to the normal [page configuration](/docs/profile-filament-plugin/{version}/configuration/pages#user-content-configurable-profile-pages) methods available, the security page also has some additional configuration methods it accepts to make customizing the page a little more convenient.

### Hiding the update password form

The security page allows you to hide the update password form entirely by passing a boolean `false` to the `updatePasswordForm()` method on the page configuration instance:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Filament\Pages\Profile\Security;

ProfileFilamentPlugin::make()
    ->securityPage(
        Security::make()
            ->slug('security')
            ->updatePasswordForm(false)
    )
```

### Hiding the multi-factor authentication manager

If you have MFA enabled on the plugin but want to hide our MFA manager component, you can pass a boolean `false` to the `manageMultiFactorForm` method on the page configuration instance:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Filament\Pages\Profile\Security;

ProfileFilamentPlugin::make()
    ->securityPage(
        Security::make()
            ->slug('security')
            ->manageMultiFactorForm(false)
    )
```

### Using a custom multi-factor authentication manager

As an alternative method to [replacing](/docs/profile-filament-plugin/{version}/configuration/pages#user-content-livewire-components) the `MultiFactorAuthenticationManager` Livewire component, you can pass a class-string of your Livewire component to the `manageMultiFactorForm()` method on the page configuration instance.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Filament\Pages\Profile\Security;

ProfileFilamentPlugin::make()
    ->securityPage(
        Security::make()
            ->slug('security')
            ->manageMultiFactorForm(managerClass: YourCustomComponent::class)
    )
```
