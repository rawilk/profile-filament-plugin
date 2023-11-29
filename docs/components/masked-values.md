---
title: Masked Values
sort: 1
---

## Introduction

There may be situations where you need to show a sensitive piece of data on the page, but initially hide it from the user. This where our `MaskedEntry` info list item comes in. It will initially blur the value, and then once clicked on it will reveal the actual value it's hiding.

Here's a screenshot of the entry being used to initially hide a user's ID on their profile page:

![masked entry](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/masked-entry.png)

## Usage

In your component's info list schema, you can add the `MaskedEntry` for a model's field like this:

```php
use Rawilk\ProfileFilament\Filament\Infolists\Components\MaskedEntry;
use Illuminate\Support\Str;

protected function infolistSchema(): array
{
    return [
        // ...
        MaskedEntry::make('id')
            ->label('User id')
            ->copyable()
    ];
}
```

## Mask Using

By default, we will use Laravel's `Str::mask()` helper to render the masked value as all '\*' characters underneath the blur. This is for security, so the actual value cannot be inspected underneath the blur with element inspector.

If you want to customize how the value is masked, you may provide a callback to `maskUsing`. This is useful if you want the value to look a little more realistic underneath the blur.

```php
MaskedEntry::make('id')
    ->maskUsing(fn ($state): string => 'usr_' . Str::mask($state, '*'))
```

## Sudo Confirmation

If you want your user to have to enter [sudo mode](/docs/profile-filament-plugin/{version}/advanced-usage/sudo) before they can reveal the value, you can require sudo mode like this:

```php
MaskedEntry::make('id')
    // ...
    ->requireSudoConfirmation()
```
