---
title: Profile
sort: 1
---

## Introduction

The Profile page is typically the starting page for the user profile in this plugin. By default, it will display a user's name and the date their account was created, along with a form
to edit their name. This of course can be customized according to your application's requirements.

Here is a screenshot of what the base Profile page will look like:

![base profile page](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/base-profile.png?raw=true)

And here is the default edit form:

![base profile edit form](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/base-profile-edit-form.png?raw=true)

## Customization

For basic applications, the plugin's implementation may be enough. However, most applications will probably have a need to override the `ProfileInfo` component.

Let's say you need to show a user's timezone and allow them to edit it as well on their profile info. This can easily be accomplished by overriding the `ProfileInfo` component
and [swapping it out](/docs/profile-filament-plugin/{version}/customizations/page-customization#user-content-swap-components).

To accomplish this, the two main methods we need to override are the `infolistSchema` and `formSchema` methods. There are other methods that can be overridden, such as `saveForm`, but what we are going to show below should be sufficient in most cases.

```php
namespace App\Livewire;

use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;

class CustomProfileInfo extends ProfileInfo
{
    protected function infolistSchema(): array
    {
        return [
            $this->nameTextEntry(),
            TextEntry::make('timezone'),
            $this->createdAtTextEntry(),
        ];
    }

    protected function formSchema(): array
    {
        return [
            $this->nameInput(),
            Select::make('timezone')
                ->options([
                    // ...
                ])
        ];
    }
}
```

Now, all you need to do is swap the component out in your panel's service provider:

```php
use App\Livewire\CustomProfileInfo;
use Rawilk\ProfileFilament\Filament\Pages\Profile\ProfileInfo as ProfileInfoPage;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Livewire\Profile\ProfileInfo;

$panel->plugin(
    ProfileFilamentPlugin::make()
        ->swapComponent(
            page: ProfileInfoPage::class,
            component: ProfileInfo::class,
            newComponent: CustomProfileInfo::class,
        )
)
```

> {tip} It's not necessary to extend the plugin's livewire component in this case; you are free to use a completely custom class if you want.

## Events

By default, our `editAction` will dispatch the `\Rawilk\ProfileFilament\Events\Profile\ProfileInformationUpdated` event, which you can listen for in your application if needed. The event will receive the authenticated user.
