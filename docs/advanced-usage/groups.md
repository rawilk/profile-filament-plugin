---
title: Navigation Groups
sort: 5
---

## Introduction

This package makes use of the [Filament Inner Nav](https://github.com/rawilk/filament-inner-nav) package to render its navigation links. A feature of that package is sub-navigation groups. This documentation page will detail how to group pages into groups.

## Groups

To group profile navigation links together, you first need to create a group page class. This class **must extend** the `ProfilePageGroup` class. For our example, we're going to create a "Billing" profile navigation group.

```php
use Rawilk\ProfileFilament\Filament\Pages\ProfilePageGroup;

class BillingGroup extends ProfilePageGroup
{
    protected static int $sort = 1;

    protected static bool $collapsible = true;

    protected static string $icon = 'heroicon-o-credit-card';

    public static function getLabel(): ?string
    {
        return __('Billing');
    }
}
```

Now on a custom page class, you can reference this group, like this:

```php
class BillingPreferences extends Page
{
    use IsProfilePage;

    protected static string $view = 'filament.pages.billing-preferences';

    // ...

    public static function innerNavGroup(): ?string
    {
        return BillingGroup::class;
    }
}
```

When the `BillingPreferences` page is [registered](/docs/profile-filament-plugin/customizations/additional-pages), it will be rendered under a collapsible group labeled "Billing".

The default pages offered by this package can also be placed into navigation groups. For example, if you wanted to put the `Profile` page inside the `BillingGroup` group we created earlier, you can do it like this when registering the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->profile(
            group: BillingGroup::class,
        )
)
```

> {tip} When pages are put inside a group class, their sort orders are relative to the group they are in.

## Nesting Groups and Pages

It is possible to nest navigation groups and pages **one** level deep. The top level group's label is rendered as a heading with a border on top of it to separate it from other navigation links. Here is a screenshot of a group that has a child page link, and a child collapsible group.

![nested group](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/nested-group.png?raw=true)

To achieve what is shown in the screenshot, we will be creating a few pages and page group classes, and then be registering those custom pages with the plugin. Note: We are pushing the top level group to the bottom of the navigation, but it can be inserted somewhere in the middle of the navigation as well.

```php
use Rawilk\ProfileFilament\Filament\Pages\ProfilePageGroup;

class TopLevelGroup extends ProfilePageGroup
{
    protected static int $sort = 50;

    // Important to set this to false
    protected static bool $collapsible = false;

    public static function getLabel(): ?string
    {
        return 'Parent Group';
    }
}
```

Note that our parent group has `$collapsible` set to `false`. It is not allowed to have a multiple groups inside a collapsible group.

```php
use Rawilk\ProfileFilament\Filament\Pages\ProfilePageGroup;

class NestedGroup extends ProfilePageGroup
{
    protected static int $sort = 2;

    // Must be set to true
    protected static int $collapsible = true;

    protected static string $icon = 'heroicon-o-credit-card';

    public static function getLabel(): ?string
    {
        return 'Nested Group';
    }

    public static function parentGroup(): ?string
    {
        return TopLevelGroup::class;
    }
}
```

Note that the nested group has `$collapsible` set to `true`. It is not permitted to nest non-collapsible groups.

```php
use Filament\Pages\Page;
use Rawilk\ProfileFilament\Concerns\IsProfilePage;

class TopLevelPage extends Page
{
    use IsProfilePage;

    public static function getNavigationLabel(): string
    {
        return 'Parent Group Page';
    }

    public static function getSlug(): string
    {
        return 'profile/page-1';
    }

    public static function innerNavSort(): int
    {
        return 1;
    }

    public static function innerNavGroup(): ?string
    {
        return TopLevelGroup::class;
    }
}
```

```php
class NestedGroupPage extends Page
{
    use IsProfilePage;

    public static function getNavigationLabel(): string
    {
        return 'Nested Group Page';
    }

    public static function getSlug(): string
    {
        return 'profile/page-2';
    }

    public static function innerNavSort(): int
    {
        return 1;
    }

    public static function innerNavGroup(): ?string
    {
        return NestedGroup::class;
    }
}
```

Now we just need to register these pages like normal on the plugin:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->addPage(className: TopLevelPage::class)
    ->addPage(NestedGroupPage::class)
```
