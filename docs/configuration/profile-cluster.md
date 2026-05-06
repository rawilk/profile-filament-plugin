---
title: Profile Cluster
sort: 2
---

## Introduction

This plugin makes use of Filament's [Clusters](https://filamentphp.com/docs/5.x/navigation/clusters) for the profile pages. I prefer to handle the user profile this way instead of using Filament's default profile. If this is undesirable behavior, you should [disable](/docs/profile-filament-plugin/{version}/configuration/pages#disabling-profile-pages) all the profile pages and define your own instead.

## Customizing the cluster slug

By default, each profile page url slug is prefixed with `/profile` from the Cluster. You can use a different slug by using the `profileCluster()` method on the plugin:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->profileCluster('custom-slug')
```

## Changing the default profile page

See the [default profile page](/docs/profile-filament-plugin/{version}/configuration/pages#default-profile-page) section for more information on changing the initial page the cluster shows when the root cluster slug is visited.
