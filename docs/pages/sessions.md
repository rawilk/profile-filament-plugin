---
title: Sessions
sort: 4
---

## Introduction

The Sessions profile page allows a user to log out of all other browser sessions if they need to, as well as listing out their browser sessions if you're using the [database session driver](https://laravel.com/docs/13.x/session#database).

> {note} To use any of the logout other sessions functionality, your users must have a password set on their accounts.

Here is a screenshot of this page when you're using the database driver:

![sessions](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/pages/sessions.png?raw=true)

## Default livewire components

The sessions page consists of Livewire components that provide the page's functionality. You can [extend, replace, or remove](/docs/profile-filament-plugin/{version}/configuration/pages#user-content-livewire-components) any of the components on this page.

The default Livewire components rendered onto the sessions page include:

- `Rawilk\ProfileFilament\Livewire\Sessions\SessionManager`

## Log out all other sessions

Regardless of the database driver being used in your application, your users will always be able to log out all other browser sessions. The Filament `LogoutAllSessionsAction` action makes use of Laravel's `Auth::logoutOtherDevices('password')` functionality, so we require a user's password to be confirmed before this action can be run.

Here is a screenshot of what this confirmation will look like:

![log out all sessions](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/actions/log-out-all-sessions.png?raw=true)

## Session List

As mentioned in the introduction, you will need the database driver to list a user's sessions out. Each session listed will show the device, browser name, ip address, and when the session was last active if it's from a different device. There will also be a log device out action available for sessions that are from different devices.
