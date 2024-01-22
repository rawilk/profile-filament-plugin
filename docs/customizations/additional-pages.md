---
title: Additional Pages
sort: 3
---

## Introduction

While the plugin offers a decent starting point, you may find yourself needing to add additional profile pages, depending on your application's requirements. This documentation page will detail the process and requirements for adding your own profile pages.

## Define Your Page

First, you need to define a page class. Your page class should extend Filament's `Page` component. In this package, we are making use of Filament's [Clusters](https://filamentphp.com/docs/3.x/panels/clusters) feature, so you'll need to define the `Profile::class` as your page's `$cluster` (see example below).

Here's a simple example of a custom page you can define:

```php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Rawilk\ProfileFilament\Filament\Clusters\Profile;

class Notifications extends Page
{
    protected static string $view = 'filament.pages.notifications';
    
    protected static ?string $cluster = Profile::class;

    public static function getNavigationLabel(): string
    {
        return __('Notifications');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-bell';
    }

    public static function getSlug(): string
    {
        // do not prefix with `profile`, since the cluster's
        // slug will already be prefixed to this slug.
        return 'notifications';
    }

    public static function getTitle(): string|Htmlable
    {
        return __('Notifications');
    }
}
```

As long as your page is registered correctly in your panel, the page should automatically be added to the profile's navigation items. If you want to group
your pages together, you can provide a string to the `$navigationGroup` property on your page class.

> {tip} To offer flexibility in custom page placement, we stagger the default page sorts from the package in increments of 10.

> {note} Make sure your panel knows about your page. If your page isn't auto-discovered by your panel, you may need to add it to your panel's `pages`.
