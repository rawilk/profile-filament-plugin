---
title: Render Hooks
sort: 3
---

## Introduction

This package offers a few of its own [render hooks](https://filamentphp.com/docs/5.x/advanced/render-hooks) to allow you to render Blade content at certain points in its views.

## Registering render hooks

To register render hooks for the package, you can call `FilamentView::registerRenderHook()` from a service provider or middleware. The first argument is the name of the render hook, and the second argument is a callback that returns the content to be rendered.

```php
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use Rawilk\ProfileFilament\Enums\RenderHook;

FilamentView::registerRenderHook(
    RenderHook::SudoChallengeAfter->value,
    fn (): string => Blade::render('<div>Custom content after sudo challenge form</div>')
);
```

> {tip} The package's render hooks work the same way as any of Filament's render hooks do.

## Available render hooks

Using class `Rawilk\ProfileFilament\Enums\RenderHook`

- `RenderHook::EmailVerificationPromptAfter` - Before the email verification prompt content
- `RenderHook::EmailVerificationPromptBefore` - After the email verification prompt content
- `RenderHook::MultiFactorChallengeAfter` - After a MFA challenge form
- `RenderHook::MultiFactorChallengeBefore` - Before a MFA challenge form
- `RenderHook::SudoChallengeAfter` - After a sudo challenge form
- `RenderHook::SudoChallengeBefore` - Before a sudo challenge form
