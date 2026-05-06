---
title: Miscellaneous
sort: 50
---

## User timezone

When the plugin displays timestamps for the user, we will try to use the user's timezone to show a localized time for the user. By default we try to use a `timezone` attribute on the user model. If a `null` value is found for that attribute, we will fall back to `UTC` for the user's timezone.

You can use the `ProfileFilament` service class in a service provider to define a callback that the package should use instead to resolve the correct timezone to use for a given user:

```php
use Rawilk\ProfileFilament\ProfileFilament;
use App\Models\User;

ProfileFilament::findUserTimezoneUsing(
    fn (User $user): string => $user->tz
);
```
