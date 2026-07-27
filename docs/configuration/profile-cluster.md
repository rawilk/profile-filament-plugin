---
title: Profile Cluster
sort: 2
---

## Introduction

This plugin makes use of Filament's [Clusters](https://filamentphp.com/docs/5.x/navigation/clusters) for the profile pages. I prefer to handle the user profile this way instead of using Filament's default profile. If this is undesirable behavior, you should [disable](/docs/profile-filament-plugin/{version}/configuration/pages#disabling-profile-pages) all the profile pages and define your own instead.

## Tenancy

Profile routes are not tenant-aware by default. This keeps account-level pages available to users who do not belong to a tenant and gives them URLs such as `/admin/profile/user`.

When Filament tenancy is enabled on the panel, the profile pages still use the standard panel layout, including the sidebar and tenant switcher. Since a global profile route does not contain a tenant parameter, the plugin uses Filament's default tenant for the authenticated user while rendering the panel navigation. This does not make the profile route tenant-aware or run the panel's tenant middleware.

You may customize how this tenant is resolved with the `resolveTenantUsing()` method. For example, you could use the user's last active team:

```php
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->resolveTenantUsing(
        fn (User $user): ?Model => $user->lastTeam,
    )
```

The callback may return `null` when the user does not have an available tenant. In that case, the profile page remains accessible, but tenant-dependent panel navigation is hidden.

If profile data belongs to a tenant in your application, you may opt into tenant-aware profile routes:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->tenantAware()
```

Tenant-aware profile pages include the current tenant in their URLs and run the panel's tenant middleware.

## Customizing the cluster slug

By default, each profile page url slug is prefixed with `/profile` from the Cluster. You can use a different slug by using the `profileCluster()` method on the plugin:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->profileCluster('custom-slug')
```

## Changing the default profile page

See the [default profile page](/docs/profile-filament-plugin/{version}/configuration/pages#default-profile-page) section for more information on changing the initial page the cluster shows when the root cluster slug is visited.
