---
title: Additional Pages
sort: 3
---

## Introduction

While the plugin offers a decent starting point, you may find yourself needing to add additional profile pages, depending on your application's requirements. This documentation page will detail the process and requirements for adding your own profile pages.

## Define Your Page

First, you need to define a page class. Your page class should extend Filament's `Page` component, and use the `IsProfilePage` trait. This trait will help the plugin render your page's navigation link correctly.

Here's a simple example of a custom page you can define:

```php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Rawilk\ProfileFilament\Concerns\IsProfilePage;

class Notifications extends Page
{
    use IsProfilePage;

    protected static string $view = 'filament.pages.notifications';

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
        return 'profile/notifications';
    }

    // Define where your component will render in the navigation
    public static function innerNavSort(): int
    {
        return 5;
    }

    public static function getTitle(): string|Htmlable
    {
        return __('Notifications');
    }

    // optional
    // uncomment if you're using nav groups
    // public static function innerNavGroup(): ?string
    // {
        // return YourGroup::class;
    // }

    // ...
}
```

In your page's view file, it's important to use the `layout` component provided by this component. Here's an example view file you can make for your page:

```html
<!-- livewire/notifications.blade.php -->
<x-profile-filament::layout>
    <div>Your content here.</div>
</x-profile-filament::layout>
```

## Register Your Page

Once you have a custom page defined, you can register it on the plugin in your panel's service provider:

```php
use App\Filament\Pages\Notifications;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->addPage(className: Notifications::class)
)
```

If you defined your page like the example we provided above, your page should show up as the second navigation link on the user's profile.

> {tip} To offer flexibility in custom page placement, we stagger the default page sorts from the package in increments of 10.

> {note} Make sure your panel knows about your page. If your page isn't auto-discovered by your panel, you may need to add it to your panel's `pages`.
