---
title: Profile
sort: 1
---

## Introduction

The Profile page is typically the starting page for the user profile in this plugin. By default, it will display a user's name and the date their account was created, along with a form to edit their name. This, of course, can be customized according to your application's requirements.

Here is a screenshot of what the base Profile page will look like:

![base profile page](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/pages/profile.png?raw=true)

And here is the default edit form:

![base profile edit form](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/pages/profile-edit.png?raw=true)

## Customization

Unless your application is very basic, you will probably want to customize the information shown on the profile information component. The easiest way to handle this would be to provide your own schema to the `ProfileInfolist` schema class in a service provider:

```php
use Rawilk\ProfileFilament\Filament\Schemas\Infolists\ProfileInfolist;
use Filament\Schemas\Components\Section;
use Rawilk\ProfileFilament\Filament\Actions\EditProfileInfoAction;

ProfileInfolist::configureComponents(fn (): array => [
    Section::make('Custom profile info')
        ->headerActions([
            EditProfileInfoAction::make(),
        ])
        ->schema([
            // ...
        ])
]);
```

The callback you provide will receive a `$user` object as a parameter if you need it.

> {tip} If you are providing your own schema for this, you'll probably also want to use a different edit profile action to allow the user to edit those fields as well.

## Use a custom profile info page

If you'd rather use your own page class, you are free to do that too. You can provide a class-string of your custom profile info page class to the `profileInfoPage()` method on the plugin:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->profileInfoPage(YourCustomProfileInfo::class)
```

See [Pages](/docs/profile-filament-plugin/{version}/configuration/pages) for more information on customizing the pages.

## Disable profile info

If you'd rather disable the page entirely, you can provide a `null` value to the `profileInfoPage()` method on the plugin:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->profileInfoPage(null)
```

> {note} The profile info page is the default profile page. If you disable it, be sure to provide a different [default profile page](/docs/profile-filament-plugin/{version}/configuration/pages#user-content-default-profile-page) to the `useDefaultProfilePage()` method on the plugin.
