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
