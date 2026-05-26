---
title: Cross-Domain WebAuthn
sort: 4
---

## Introduction

When using the [WebAuthn](/docs/profile-filament-plugin/{version}/auth/multi-factor-authentication#user-content-webauthn-provider) multi-factor provider you are typically limited to using passkeys and security keys on a single domain. This package attempts to work around this limitation with WebAuthn.

This limitation isn't typically a problem for most applications, as they aren't accessed from multiple domains. The problem with this arises with some multi-tenant applications that allow tenants to use custom domains. Now, if a user registers a WebAuthn credential on the tenant's custom domain in your application, they can only use the credential when accessing the site on that domain. However, they won't be able to use that credential if they are able to access the application on your central SaaS domain or on another tenant's domain if they have access to it.

This is where our cross-domain WebAuthn solution comes in. By setting the [Relying Party](https://webauthn-doc.spomky-labs.com/prerequisites/the-relying-party) ID to a central auth domain, we will use that instead to register and authenticate a user's WebAuthn credentials.

## Set the relying party id

To get started, you first need to set the Relying Party ID to a domain that all users in your application can access. You can either set this directly in the `webauthn.relying_party.id` configuration key in the `config/profile-filament.php` file, or by just setting the `WEBAUTHN_RELYING_PARTY_ID` environment variable in the `.env` file of your application. We'll use the environment file here:

```bash
WEBAUTHN_RELYING_PARTY_ID=my-saas.com
```

Here we set the Relying Party ID to `my-saas.com`, however, you can use a subdomain of your domain as well if you prefer, such as `auth.my-saas.com`. We don't recommend setting it to a subdomain, however, if you will be using WebAuthn in your application on the root domain. In that case, use your root domain as the Relying Party ID.

Now when a sudo challenge or MFA challenge is issued for WebAuthn, we will open up a popup window in JavaScript to use the domain being used for the Relying Party ID. This window will authenticate the credential using a simplfied WebAuthn form and then send back a message to the parent window to continue the authentication like normal.

No matter which domain is being used to access the application, whether it be `tenant-1.com`, `tenant-2.com`, `my-saas.com`, `tenant-1.my-saas.com`, etc., the WebAuthn authentication and registration process will work seamlessly across all domains of your application.

> {note} We will only open the popup window when necessary to provide a smooth user experience. If the user was on `tenant-1.my-saas.com` in this example, we would not open the window, since the root host name is the same as your Relying Party ID.

Need more help choosing a Relying Party ID? See [these docs](https://webauthn-doc.spomky-labs.com/prerequisites/the-relying-party#how-to-determine-the-relying-party-id) for more information on choosing a Relying Party ID.

## Disabling cross-domain webauthn

If you'd prefer not to use this feature of the package, or would rather implement it yourself with a custom WebAuthn provider, you can disable it on the plugin by telling the `crossDomainWebauthn()` method to not register the routes:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->crossDomainWebauthn(
        registerRoutes: false,
    )
```
