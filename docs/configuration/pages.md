---
title: Pages
sort: 1
---

## Introduction

Each of the provided profile pages from this plugin has certain configurations that can be set on them. The configurations detailed below are common to all profile pages, unless otherwise specified.

## Introducing pages

By default, the plugin enables each of the profile pages it provides, and each of them has their own [configuration](https://filamentphp.com/docs/5.x/plugins/configurable-resources-and-pages#configurable-resources-and-pages) class.

There are four profile pages provided by this plugin:

- [Profile Info](/docs/profile-filament-plugin/{version}/pages/profile) is used to show profile information about the user
- [Security](/docs/profile-filament-plugin/{version}/pages/security) is used to update a user's password and multi-factor authentication settings
- [Sessions](/docs/profile-filament-plugin/{version}/pages/sessions) is used to show and manage a user's active sessions
- [Settings](/docs/profile-filament-plugin/{version}/pages/settings) is used to show and update a user's email and provides a delete account action

### Default profile page

The profile this plugin creates has a starting profile page, or as we usually refer to it as the default profile page. When the user clicks on the profile link in their user menu or if they visit the root `/profile` cluster slug, this is the page they will be redirected to.

We default this to a sensible default of the [Profile Info](/docs/profile-filament-plugin/{version}/pages/profile) page. To change this, you can use the `useDefaultProfilePage()` method on the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->useDefaultProfilePage(MyRootProfilePage::class)
```

## Configurable profile pages

As mentioned above, each of the profile pages uses a Filament page configuration. To use a given page's configuration, you need to create an instance of it and pass that instance into the page's corresponding method on the plugin. Each of the pages have a static `make()` method on them to create an instance of their page configuration.

### Page slugs

The slugs of each profile page can be customized with the `slug()` method on its configuration. For example, if you wanted to change the url slug for the security page, you could do it like this:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Filament\Pages\Profile\Security;

ProfileFilamentPlugin::make()
    ->securityPage(Security::make()->slug('authentication'))
```

> {note} Any time you use a page configuration, you should define the slug on it, otherwise a slug of `default` will be used by Filament. I'm not sure at this time if there is a way to prevent that with the plugin.

### Page title

If you don't want to publish and edit the language lines for the package, you can also use the `title()` method on a page configuration to customize the heading and title of a given page.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;

ProfileFilamentPlugin::make()
    ->profileInfoPage(ProfileInfo::make()->title('My Profile'))
```

### Navigation label

Like with the page title, you can customize a given page's navigation label through its page configuration class via the `navigationLabel()` method instead of modifying the package's language lines.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;

ProfileFilamentPlugin::make()
    ->profileInfoPage(ProfileInfo::make()->navigationLabel('My Profile Nav Label'))
```

### Navigation icon

To customize the navigation icon of a given page, you can use the `navigationIcon()` mnethod on its configuration class:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;
use Filament\Support\Icons\Heroicon;

ProfileFilamentPlugin::make()
    ->profileInfoPage(ProfileInfo::make()->navigationIcon(Heroicon::OutlinedUserPlus))
```

> {tip} You can remove the navigation icon from the page by passing a `null` value instead.

### Navigation sort

You can change the sort order of the profile pages by using the `navigationSort()` method on a given page's configuration. We stagger the sort order of each profile page by 10, so that you have room to insert your own pages between each of them.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;

ProfileFilamentPlugin::make()
    ->profileInfoPage(ProfileInfo::make()->navigationSort(5))
```

### Navigation group

By default, there are no navigation groups on the profile cluster. You are free to move each of the pages into their own groups by using the `navigationGroup()` method on their configuration classes:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;
use Rawilk\ProfileFilament\Filament\Pages\Profile\Sessions;

ProfileFilamentPlugin::make()
    ->profileInfoPage(ProfileInfo::make()->navigationGroup('My group'))
    ->sessionsPage(Sessions::make()->navigationGroup('My group'))
```

> {tip} If you need to translate the navigation group label from a language file, pass the language line key to the `navigationGroup()` method, and pass a boolean `true` as the second parameter to it.

Here is a screenshot of what that would look like by default:

![profile nav groups](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/pages/profile-nav-groups.png?raw=true)

## Livewire components

Each profile page in the plugin merely wraps one or more Livewire components on the page. Using the `components()` method on a page's configuration, you can replace, extend, or re-arrange the order the components are rendered on the page.

This example will change the Profile Info page to show only a custom `EditUserProfileForm` Livewire component:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Filament\Pages\Profile\ProfileInfo;
use App\Livewire\Profile\EditUserProfileForm;

ProfileFilamentPlugin::make()
    ->profileInfoPage(
        ProfileInfo::make()
            ->slug('user')
            ->components([
                EditUserProfileForm::class,
            ])
    )
```

> {tip} If you just want to append your own components to the page, you can pass a boolean `true` value as the second parameter to the `components()` method to merge your components in with the page default components.

### Customize livewire component schemas

Many of the Livewire components used in the profile pages can have their schemas customized instead of either extending the component yourself or using your own component instead. Most of them have infolists or other schemas that can have a custom Filament schema by using a static `configureComponents()` method on the [component class](https://filamentphp.com/docs/5.x/resources/code-quality-tips#using-component-classes) itself in a service provider.

This may be a preferable method of customizing the Livewire components if you find yourself just wanting to use a different schema for the component. Each of the infolists in this plugin that have configurable schemas are located in the `Rawilk\ProfileFilament\Filament\Schemas\Infolists` namespace.

For example, if you wanted to modify the infolist schema for that is shown on the [Profile Info](/docs/profile-filament-plugin/{version}/pages/profile) page, you could configure the `ProfileInfolist` component class in a service provider:

```php
use Rawilk\ProfileFilament\Filament\Schemas\Infolists\ProfileInfolist;
use Illuminate\Support\ServiceProvider;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        ProfileInfolist::configureComponents(fn () => [
            Section::make()
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('timezone'),
                ])
        ]);
    }
}
```

Depending on the infolist component class you're configuring there may be some parameters your callback function can accept, such as the authenticated `$user` object. You should consult with the infolist you're configuring to see which parameters are available to your callback; your callback will typically be evaluated in a `resolveComponents()` method on the component class.

> {tip} Your callback function to `configureComponents()` should return an array of Filament schema components.

## Disabling profile pages

Each of the available profile pages can be disabled entirely if you don't want to use them in the panel. Pass in a `null` value to the page's corresponding method on the plugin.

To disable the session management page for example:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->sessionsPage(null)
```
