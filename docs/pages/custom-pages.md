---
title: Custom Pages
sort: 5
---

## Introduction

Your application may need additional profile pages that are not provided by the plugin. Since we use a [cluster](/docs/profile-filament-plugin/{version}/configuration/profile-cluster) to group all the profile pages together, it's easy to create and add your own profile pages.

## Create your profile page

Create a [Filament Page](https://filamentphp.com/docs/5.x/navigation/custom-pages#creating-a-page) as you normally would for your panel, and then set the page's `$cluster` property to `ProfileCluster`.

Here's a simple example to add a new profile page and place it under the [Profile Info](/docs/profile-filament-plugin/{version}/pages/profile) page in the cluster navigation:

```php
namespace App\Filament\Pages;

use BackedEnum;use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Text;
use Rawilk\ProfileFilament\Filament\Clusters\ProfileCluster;
use Filament\Support\Icons\Heroicon;

class NotificationSettings extends Page
{
    protected static ?string $cluster = ProfileCluster::class;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    public static function getNavigationLabel(): string
    {
        return __('Notification Settings');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make('Hello World!'),
            ]);
    }
}
```

As long as your page is picked up by your panel, it will be automatically added to the profile cluster navigation. You are able to customize anything about the page like you normally can with any other Filament page; you are just required to set the `$cluster` property on it so that it shows up properly in the profile cluster navigation.

> {tip} For page sort flexibility, we stagger each of the plugin's profile pages sort orders in increments of `10`.
