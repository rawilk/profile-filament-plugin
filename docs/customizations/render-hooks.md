---
title: Render Hooks
sort: 4
---

## Introduction

There are a few points where we allow you to render Blade content in our views. To accomplish this, we're using Filament's [render hooks](https://filamentphp.com/docs/3.x/support/render-hooks#registering-render-hooks).

## Available Render Hooks

- `profile-filament::mfa.settings.before` - Right before two-factor methods are rendered
- `profile-filament::mfa.methods.after` - Right before recovery options are rendered
- `profile-filament::mfa-challenge.start` - Right after the title on the full-page mfa challenge form
