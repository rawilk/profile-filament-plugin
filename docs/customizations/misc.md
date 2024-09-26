---
title: Miscellaneous
sort: 6
---

## User Timezone

When we display certain timestamps in the package's views, we will try to use the user's timezone to show a localized time for the user. By default, we will assume there is a `timezone` attribute on the user model. If a `null` value is found for the user's timezone, we will fallback on `UTC` for the user's timezone.

If you need a different way to resolve a user's timezone, you can define a callback in a service provider like this:

```php
use Rawilk\ProfileFilament\ProfileFilament;

public function boot(): void
{
    ProfileFilament::findUserTimezoneUsing(fn ($user) => $user->tz);
}
```

The callback you provide to `findUserTimezoneUsing` will receive a user model as its only parameter.

## User Menu

The plugin automatically adds a root profile page entry to the user dropdown menu that Filament creates. By default, the root profile page is set to `\Rawilk\ProfileFilament\Filament\Pages\Profile\ProfileInfo::class`, however you are free to change it to something else.

![user menu](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/user-menu.png?raw=true)

### Root Profile Page

If you want to change the root profile page, you can use the `usingRootProfilePage` method on the plugin. The value you provide should be a class name to a page component.

```php
use Rawilk\ProfileFilament\Filament\Pages\Profile\Security;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->usingRootProfilePage(Security::class)
```

### Root Profile Slug

By default, we use `profile` as the slug for the `Profile` cluster. If your panel's path is `admin`, any profile pages will be prefixed with `/admin/profile/`. If you need to change the root profile path, you can do it like this on the plugin:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->usingClusterSlug('user');
```

#### Multiple Panels Notice

If you're using the plugin on multiple panels in your app and decide to use a different cluster slug for each panel, you may run into issues with custom pages. The way Filament resolves page urls and slugs is by using static functions. This normally isn't a problem, however in php static properties and methods are shared across all page instances, so this becomes an issue when each panel wants to have its own cluster slug.

The way we've solved this in the plugin is by creating the `\Rawilk\ProfileFilament\Concerns\HasPanelPageRoutes` trait. This trait will override some page logic for defining and resolving routes. Simply add this trait to your custom page class, and we will resolve the correct cluster slug for you.

Let's say you an admin panel, with a cluster slug of `/profile-settings`, and an app panel with a cluster slug of `/user-settings`. If you add our trait to a custom page class (with a slug of `/my-custom-profile-page`), it's url path will resolve to `/admin/profile-settings/my-custom-profile-page` and `/app/user-settings/my-custom-profile-page`, respectively.

### Menu Icon

The default icon for the user menu item is `heroicon-o-cog-6-tooth`. You can change this by using the `usingUserMenuIcon` method on the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->usingUserMenuIcon('heroicon-o-user')
```

### Hiding the Menu Item

If you want to hide the user profile page from the user menu all-together, you can use the `hideFromUserMenu` method on the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->hideFromUserMenu()
```
