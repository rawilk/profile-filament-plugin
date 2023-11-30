---
title: Page Customization
sort: 1
---

## Introduction

Each of the profile pages offered by this plugin are highly customizable. Below we will detail the various ways you can customize the pages.

## Page Registration

The easiest way to customize any of the profile pages is when you register the plugin. There are methods on the plugin for each of the profile pages, and each of them
have the same parameters:

-   `profile` - The base profile page
-   `accountSecurity` - Update password & mfa settings page
-   `accountSettings` - Update email address and delete account
-   `sessions` - View and manage sessions

Here is an example on how to customize the `profile` page:

```php
$panel->plugin(
    ProfileFilamentPlugin::make()
        ->profile(
            enabled: true,
            slug: 'profile',
            icon: 'heroicon-o-user-circle',
            className: \Rawilk\ProfileFilament\Filament\Pages\Profile::class,
            components: [],
            sort: 0,
            group: null,
        )
)
```

Each parameter is optional, so you can specify only the ones you need to customize. Here is a breakdown of each parameter:

-   `enabled` - Setting this to false will completely remove the page from the user's profile.
-   `slug` - The URL slug to use for the page.
-   `icon` - The navigation icon for the page.
-   `className` - The class name of the page. This is useful if you want to extend the page or use your own implementation of it.
-   `components` - An array of livewire components to register onto the page. These will be merged with the default components for the page. To remove certain components from a page, use the [Features](/docs/profile-filament-plugin/{version}/customizations/features) object on the plugin.
-   `sort` - An integer value indicating where the page should appear in the profile inner navigation.
-   `group` - Should be the class name of a [Group](/docs/profile-filament-plugin/{version}/advanced-usage/groups) to render the page's navigation link under.

If you just want to edit the sort order for the profile page, for example, you could do it like this:

```php
$panel->plugin(
    ProfileFilamentPlugin::make()
        ->profile(
            sort: 10,
        )
)
```

## Extended Pages

For more control over a certain profile page offered by the package, you may choose to either extend the package's class for that page, or use your own class for it. If you opt to use your own class without extending the package's class, you need to make sure you use the `IsProfilePage` trait on your class. The class should also typically extend the `Filament\Pages\Page` class as well.

```php
use Filament\Pages\Page;
use Rawilk\ProfileFilament\Concerns\IsProfilePage;

class MyCustomClass extends Page
{
    use IsProfilePage;
    // ...
}
```

Here is an example on how you could override the `Profile` page with your own page class:

```php
use Rawilk\ProfileFilament\Filament\Pages\Profile;

class CustomProfile extends Profile
{
    #[Computed]
    public function registeredComponents(): Collection
    {
        return collect(...);
    }
}
```

Next, tell the plugin to use your custom class instead for the profile page:

```php
$panel->plugin(
    ProfileFilamentPlugin::make()
        ->profile(
            className: CustomProfile::class,
        )
)
```

## Add Page Components

Most of the profile pages offered by the plugin serve as a starting point for your application. You may find that you need to add custom components to pages, such as the `Profile` page.

Let's say you want to add a section to the `Profile` page for a user to enter their social links into. Here is one way you could define a custom livewire component, and then add it to the profile page.

```php
namespace App\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class SocialLinksForm extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    // optional
    public static int $sort = 5;

    // ...
}
```

In the class above, we're defining a public static `$sort` property on the class, which will be used by the plugin for determining where to render the component on the page. This is completely optional, and can alternatively be defined when you add the component to a page on the plugin, as we will show below.

To add the new component to the profile page, we will add it like this:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use App\Livewire\SocialLinksForm;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->profile(
            components: [
                ['class' => SocialLinksForm::class, 'sort' => 5],
            ],
        )
)
```

If you want the plugin use the `$sort` property on your component class like we defined above, you can add the component like this instead:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use App\Livewire\SocialLinksForm;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->profile(
            components: [
                SocialLinksForm::class,
            ],
        )
)
```

## Swap Components

Each default page offered by the plugin offers a set of one or more livewire components, for various functionality, such as updating your profile information. While the default components may be suitable for basic applications, you may find yourself needing to customize some of them to better fit your application's requirements. This is most easily accomplished with the `swapComponent` method on the plugin. A common component you may need to override is the `ProfileInfo` component.

Here's an example of how you could define your own class, and then swap it out:

```php
namespace App\Livewire;

use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;

class CustomProfileInfo extends ProfileInfo
{
    public function formSchema(): array
    {
        return [
            $this->nameInput(),
            // Define other form inputs here
        ];
    }
}
```

Now in your panel's service provider, you can swap out the component:

```php
use App\Livewire\CustomProfileInfo;
use Rawilk\ProfileFilament\Features;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Filament\Pages\Profile;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->swapComponent(
            page: Profile::class,
            component: ProfileInfo::class,
            newComponent: CustomProfileInfo::class,
        )
)
```

Here is a breakdown of the parameters required by the `swapComponent` function:

-   `page` - The class name of the plugin page the component you're swapping is on.
-   `component` - The class name of the component you're swapping.
-   `newComponent` - The class name of your custom livewire component.

## Component Sort Order

The order the livewire components for each page are rendered in can be customized using the `setComponentSort` method on the plugin. The method takes the following parameters:

-   `page` - The class name of the plugin page the component is on.
-   `component` - The class name of the component you're sorting.
-   `sort` - An integer value of the sort order the component should have.

For example, by default on the account security page, we render the update password form above the mfa section. If you wanted to render the mfa section first, you could set the sort orders like this:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Filament\Pages\Security;
use Rawilk\ProfileFilament\Livewire\UpdatePassword;
use Rawilk\ProfileFilament\Livewire\MfaOverview;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->setComponentSort(
            page: Security::class,
            component: UpdatePassword::class,
            sort: 10,
        )
        ->setComponentSort(
            page: Security::class,
            component: MfaOverview::class,
            sort: 0,
        )
)
```

> {tip} We set the sort orders for a page's components in increments of 15, so you have plenty of room to arrange any custom components you're adding to the page in between each component, if desired.
