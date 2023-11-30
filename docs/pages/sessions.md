---
title: Sessions
sort: 4
---

## Introduction

The Sessions profile page allows a user to log out of all other browser sessions if they need to, as well as listing out their browser sessions if you're using the [database session driver](https://laravel.com/docs/10.x/session#database).

> {note} To use any of the revoke other sessions functionality, your users must have a password set on their accounts.

Here is a screenshot of this page when you're using the database driver:

![sessions](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/sessions.png?raw=true)

Each of the components on this page can be customized and swapped out for your own implementations. See [Swap Components](/docs/profile-filament-plugin/{version}/customizations/page-customization#user-content-swap-components) for more information on how to do that.

## Revoke All Sessions

Regardless of the database driver being used in your application, your users will always have the option to revoke all other browser sessions. The `revokeAll` action makes use of Laravel's `Auth::logoutOtherDevices('password')` functionality, so we require a user's password to be confirmed before this action can be run.

Here is a screenshot of what this confirmation will look like:

![revoke all sessions](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/revoke-all-sessions.png?raw=true)

## Session List

As mentioned in the introduction, you will need the database driver to list a user's sessions out. Each session listed will show the device, browser name, ip address, and when the session was last active if it's from a different device. There will also be a revoke action available for sessions that are from different devices.

Like the revoke all action, the singular revoke action requires a password confirmation from the user. This is to account for "remember me" cookies being set on other sessions. We will update the password hash on the user, which in turn will cause the password hash on that session to not match any more and will in turn invalidate the session. Our action will also update the password hash for any other sessions, so they don't end up being invalidated by this.

Here is a screenshot of the revoke single session confirmation:

![revoke session](https://github.com/rawilk/profile-filament-plugin/blob/main/assets/images/revoke-session.png?raw=true)
