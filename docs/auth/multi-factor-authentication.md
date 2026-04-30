---
title: Multi-Factor Authentication
sort: 1
---

## Introduction

Enabling multi-factor authentication (MFA) for your users can add an extra layer of security to your users' accounts. When MFA is enabled, users must perform an extra step before they are authenticated and have access to the application.

Even though Filament introduced an MFA implementation with v4.0, I still wanted it to function a little differently. The package's MFA implementation is based heavily off Filament's implementation but with some slight differences. You should use either Filament's MFA or this package's, but not both; they are not compatible with each other.

![mfa challenge](https://github.com/rawilk/profile-filament-plugin/blob/{branch}/assets/images/mfa/mfa-challenge.png?raw=true)

This package includes three providers of MFA which you can enable out of the box:

- [App Authentication](#user-content-app-authentication) uses a Google Authenticator-compatible app (such as Google Authenticator, Authy or Microsoft Authenticator apps) to generate a time-based one-time password (TOTP) that is used to verify the user.
- [Email Authentication](#user-content-email-authentication) sends a one-time code to the user's email address, which they must enter to verify their identity.
- [Webauthn Authentication](#user-content-webauthn-authentication) allows a user to use either a Passkey or a Hardware Security Key to verify their identity.

By default, the package provides a [security page](/docs/profile-filament-plugin/{version}/pages/security) with a multi-factor authentication management component that allows users to set up multi-factor authentication with. As long as at least one MFA provider is configured to the plugin, the MFA manager component will show up.

```php
use Filament\Panel;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
       // ...
       ->plugin(
            ProfileFilamentPlugin::make()
                ->multiFactorAuthentication(providers: [
                    // providers here
                ])
       );
}
```

![mfa manager](https://github.com/rawilk/profile-filament-plugin/blob/{branch}/assets/images/mfa/mfa-manager.png?raw=true)

### Prep User Model

Regardless of which MFA providers you choose to enable, your user model must implement the `HasMultiFactorAuthentication` interface and use the `InteractsWithMultiFactorAuthentication` trait which provides the necessary methods to interact with multi-factor authentication in general for the plugin.

```php
use Rawilk\ProfileFilament\Auth\Multifactor\Concerns\InteractsWithMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\HasMultiFactorAuthentication;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasMultiFactorAuthentication
{
    use InteractsWithMultiFactorAuthentication;
    // ...    
} 
```

> {tip} The plugin provides a default implementation for speed and simplicity, but you could implement the required methods yourself and customize the way your user model indicates to the plugin that a user has MFA enabled on their account.

You should also be sure to run the following database migration to ensure the necessary mfa columns are on the user model. If you [publish the package's migrations](/docs/profile-filament-plugin/{version}/installation#user-content-migrations), you will get the migration shown here added to your app's migrations.

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('users', function (Blueprint $table) {
    $table->boolean('two_factor_enabled')->default(false);
    $table->string('preferred_mfa_provider')->nullable();
});
```

Our implementation checks for the `two_factor_enabled` flag on the user model to determine if the user has MFA enabled on their account. The `preferred_mfa_provider` column is used to store a preference for the user as to which MFA provider is shown initially on MFA and [Sudo](/docs/profile-filament-plugin/{version}/auth/sudo) challenges.

### Modify Login

Our MFA process uses a separate page from the Login page, so you will need to [customize your panel's login](/docs/profile-filament/{version}/auth/login) so that the user being authenticated gets stored in the session and redirected to the multi-factor challenge instead.

## App Authentication

The plugin's implementation of app authentication allows users to register multiple authenticator apps to their account, so they are stored in a separate table. You should run the following database migration to create the table.

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Rawilk\ProfileFilament\Support\Config;

$authenticatableClass = Config::getAuthenticatableModel();

$authenticatableTableName = (new $authenticatableClass)->getTable();

Schema::create(Config::getTableName('authenticator_app'), function (Blueprint $table) use ($authenticatableClass, $authenticatableTableName) {
    $table->id();

    $table->foreignIdFor($authenticatableClass, 'user_id')
        ->constrained(table: $authenticatableTableName, indexName: 'authenticator_apps_authenticatable_fk')
        ->cascadeOnDelete();

    $table->string('name')->nullable();
    $table->text('secret')->nullable();

    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();
});
```

In the `User` model, you should implement the `HasAppAuthentication` interface and use the `InteractsWithAppAuthentication` trait which provides the necessary methods to interact with the authenticator apps for the integration.

```php
use Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\HasAppAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Concerns\InteractsWithAppAuthentication;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasAppAuthentication
{
    use InteractsWithAppAuthentication;
    
    // ...
}
```

> {note} You will need this interface and trait in addition to the `HasMultiFactorAuthentication` interface shown above on your user model.

Finally, you should add the app authentication provider to the plugin. You can use the `multiFactorAuthentication` method on the plugin and pass a `AppAuthenticationProvider` instance to it:

```php
use Filament\Panel;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            ProfileFilamentPlugin::make()
                ->multiFactorAuthentication([
                    AppAuthenticationProvider::make()
                ])            
        );
}
```

### Changing the App Code Expiration Time

App codes are issued using a time-based one-time password (TOTP) algorithm, which means that they are only valid for a short period of time before and after they are generated. The time is defined in a "window" of time. By default, the plugin uses an expiration window of `8`, which creates a 4-minute validity period on either side of the generation time (8 minutes in total).

To change the window, for example to only be valid for 2 minutes after it is generated, you can use the `codeWindow()` method on the `AppAuthenticationProvider` instance, set to `4`.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        AppAuthenticationProvider::make()
            ->codeWindow(4),   
    ])
```

### Customizing the App Authentication Brand Name

Each app authentication integration has a "brand name" that is displayed in the authentication app. By default, this is the name of your app. If you want to change this, you can use the `brandName()` method on the `AppAuthenticationProvider` instance when adding it to the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        AppAuthenticationProvider::make()
            ->brandName('Custom App Name'),   
    ])
```

### Limit App Registrations

By default, the provider limits the number of authentication apps a user may register to their account to `3`. You can either increase or decrease this limit by using the `limitAppRegistrationsTo()` method on the `AppAuthenticationProvider` instance. The example below will allow users to register up to 5 authentication apps to their account.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\App\AppAuthenticationProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        AppAuthenticationProvider::make()
            ->limitAppRegistrationsTo(5)   
    ])
```

### Setting Up Recovery Codes

If your users lose access to their multi-factor authentication app, they will be unable to sign in to your application. To prevent this, recovery codes can be used. See [Recovery](#user-content-recovery) for more information on setting up MFA recovery up.

## Email Authentication

Email authentication sends the user one-time codes to their email address, which they must enter to verify their identity.

To enable email authentication for the plugin you must first add a new column to your `users` table. The column needs to store a boolean indicating whether email authentication is enabled for the user.

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('users', function (Blueprint $table) {
    $table->boolean('has_email_authentication')->default(false);
});
```

> {note} This column **is not** part of the [publishable migrations](/docs/profile-filament-plugin/{version}/installation#user-content-migrations) from this package.

Next, you should implement the `HasEmailAuthentication` interface on the `User` model and use the `InteractsWithEmailAuthentication` trait which provides the plugin the necessary methods to interact with the column that indicates whether email authentication is enabled for the user.

```php
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Concerns\InteractsWithEmailAuthentication;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasEmailAuthentication
{
    use InteractsWithEmailAuthentication;
    
    // ...
}
```

> {tip} This plugin provides a default implementation for speed and simplicity, but you could implement the required methods yourself and customize the column name or store the value in a completely separate table.

Finally, you should activate the email authentication feature on the plugin. To do this, use the `multiFactorAuthentication()` method on the plugin and pass a `EmailAuthenticationProvider` instance to it.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\EmailAuthenticationProvider;

ProfileFilamentPlugin::make()
    ->multiFactorRecovery([
        EmailAuthenticationProvider::make(),
    ]);
```

### Changing the code expiration time.

Email codes are issued with a lifetime of 15 minutes, after which they expire.

To change the expiration period, for example to be valid for only 5 minutes after codes are generated, you can use the `codeExpiryMinutes()` method on the `EmailAuthenticationProvider` instance, set to `5`.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\EmailAuthenticationProvider;

ProfileFilamentPlugin::make()
    ->multiFactorRecovery([
        EmailAuthenticationProvider::make()
            ->codeExpiryMinutes(5),
    ]);
```

### Changing the notification

The email authentication provider provides a `VerifyEmailAuthenticationNotification` by default for sending the email notification with. You are free however to extend the notification or use your own class by providing the class name to the `notifyWith()` method on the `EmailAuthenticationProvider` instance.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\EmailAuthenticationProvider;
use App\Notifications\CustomVerifyEmailNotification;

ProfileFilamentPlugin::make()
    ->multiFactorRecovery([
        EmailAuthenticationProvider::make()
            ->notifyWith(CustomVerifyEmailNotification::class),
    ]);
```

## Webauthn Authentication

[Webauthn](https://webauthn.guide/) can be used as an alternative to App Authentication (TOTP) codes. Our implementation with the `WebauthnProvider` allows users to use either [Passkeys](https://www.webauthn.me/passkeys) or Hardware Security Keys such as a [YubiKey](https://www.yubico.com/).

To get started with this provider, you'll need to make sure you run the migration to create the `webauthn_keys` table:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Rawilk\ProfileFilament\Support\Config;

$authenticatableClass = Config::getAuthenticatableModel();

$authenticatableTableName = (new $authenticatableClass)->getTable();

Schema::create(Config::getTableName('webauthn_key'), function (Blueprint $table) use ($authenticatableClass, $authenticatableTableName) {
    $table->id();

    $table->foreignIdFor($authenticatableClass, 'user_id')
        ->constrained(table: $authenticatableTableName, indexName: 'webauthn_authenticatable_fk')
        ->cascadeOnDelete();

    $table->string('name')->nullable();
    $table->text('credential_id');
    $table->json('data');

    $table->string('attachment_type', 50)->nullable();
    $table->boolean('is_passkey')->default(false);

    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();
});
```

In the `User` model, you should implement the `HasWebauthn` interface and use the `InteractsWithWebauthn` trait which provides the necessary methods to interact with the security keys for the plugin.

```php
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts\HasWebauthn;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Concerns\InteractsWithWebauthn;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasWebauthn
{
    use InteractsWithWebauthn;
    
    // ...
}
```

Finally, you should add the webauthn provider to the plugin. You can use the `multiFactorAuthentication` method on the plugin and pass a `WebauthnProvider` instance to it:

```php
use Filament\Panel;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\WebauthnProvider;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            ProfileFilamentPlugin::make()
                ->multiFactorAuthentication([
                    WebauthnProvider::make()
                ])            
        );
}
```

### Customize the relying party

The [Relying Party](https://webauthn-doc.spomky-labs.com/prerequisites/the-relying-party) corresponds to the application that will ask the user to interact with an authenticator.

For most applications, the defaults we have set in the [config](/docs/profile-filament-plugin/{version}/installation#user-content-configuration) should work just fine. If you need to customize the relying party, you can modify the relevant config keys under the `relying_party` config key in the `profile-filament` config file.

```php
'webauthn' => [
    'relying_party' => [
        'name' => env('WEBAUTHN_RELYING_PARTY_NAME', env('APP_NAME')),
        'id' => env('WEBAUTHN_RELYING_PARTY_ID', parse_url(config('app.url'), PHP_URL_HOST)),

        // Image must be encoded as base64.
        'icon' => env('WEBAUTHN_RELYING_PARTY_ICON'),
    ]
]
```

### Limit Security Key Registrations

By default, the provider limits the number of security keys a user may register to their account to `5`. You can either increase or decrease this limit by using the `limitRegistrationsTo()` method on the `WebauthnProvider` instance. The example below will allow users to register up to 10 security keys to their account.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\WebauthnProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        WebauthnProvider::make()
            ->limitRegistrationsTo(10)   
    ])
```

## Recovery

If your users lose access to their MFA apps or devices, they will be unable to sign in to your application. To prevent this, you can generate a set of recovery codes that users can use to sign in if they lose access to their apps or devices.

Filament ties recovery codes to the AuthenticationApp provider; however, I believe recovery codes should be used no matter which MFA provider is enabled on a user account. I've decided to separate account recovery into its own provider so that it can be used in addition to any of the MFA providers.

To start with recovery, you will need to add a `two_factor_recovery_codes` column to your `users` table. The column needs to store the recovery codes. It can be a normal `text` column in a migration:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('users', function (Blueprint $table) {
    $table->text('two_factor_recovery_codes')->nullable();
});
```

Next, you should implement the `HasMultiFactorAuthenticationRecovery` interface on the `User` model and use the `InteractsWithAuthenticationRecovery` trait which provides the plugin with the necessary methods to interact with recovery codes.

```php
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\HasMultiFactorAuthenticationRecovery;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Concerns\InteractsWithAuthenticationRecovery;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use InteractsWithAuthenticationRecovery;
}
```

> {tip} The plugin provides a default implementation for speed and simplicity, but you could implement the required methods yourself and customize the column name or store the recovery codes in a completely separate table.

The plugin automatically registers our default Recover provider when you add MFA providers using the `multiFactorAuthentication()` method on the plugin, so you do not need to enable manually.

### Using a Custom Recovery Provider

For the majority of applications, the plugin's `RecoveryCodeProvider` should be more than adequate. If your application needs differ than what the default provider offers, you may define your own provider instead. Your custom provider must implement the `RecoveryProvider` interface.

Here is a simple example of a custom recovery code provider you could create:

```php
use Illuminate\Contracts\Auth\Authenticatable;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\RecoveryProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\HasMultiFactorAuthenticationRecovery;

class CustomRecoveryProvider implements RecoveryProvider
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function isEnabled(HasMultiFactorAuthenticationRecovery $user): bool
    {
        return filled($user->getAuthenticationRecoveryCodes());
    }
    
    public function needsToBeSetup(HasMultiFactorAuthenticationRecovery $user): bool
    {
        return blank($user->getAuthenticationRecoveryCodes());
    }
    
    public function getManagementSchemaComponents(): array
    {
        return [
            // Any filament schema components
        ];
    }
    
    public function generateRecoveryCodes(): array
    {
        return [
            // ...
        ];
    }
    
    public function saveRecoveryCodes(HasMultiFactorAuthenticationRecovery $user, ?array $codes): void
    {
        // Save codes for the user
    }
    
    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            // Any filament schema components
        ];
    }
    
    public function getChallengeSubmitLabel(): ?string
    {
        return 'Verify account';
    }
    
    public function getChangeToProviderActionLabel(Authenticatable $user): ?string
    {   
        return 'Use recovery code';
    }
}
```

Now you just need to tell the plugin to use your custom recovery code provider:

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication(providers: [
        // ...
    ], recoveryProvider: CustomRecoveryProvider::make())
```

Alternatively, you could use the `multiFactorRecovery()` method on the plugin instead **after** you call `multiFactorAuthentication()`.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication(providers: [
        // ...
    ])
    ->multiFactorRecovery(CustomRecoveryProvider::make())
```

### Changing the number of recovery codes that are generated

By default, the plugin generates 8 recovery codes for each user. To change this you can use the `codeCount()` method on the `RecoveryCodeProvider` instance when defining multi-factor authentication on the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\RecoveryCodeProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication(providers: [
        // ...
    ], recoveryProvider: RecoveryCodeProvider::make()->codeCount(10))
```

### Preventing users from regenerating their recovery codes

By default, users can visit their profile and regenerate their recovery codes. If you want to prevent this, you can use the `regenerableCodes(false)` method on the `RecoveryCodeProvider` instance when defining multi-factor authentication on the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\RecoveryCodeProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication(providers: [
        // ...
    ], recoveryProvider: RecoveryCodeProvider::make()->regenerableCodes(false))
```

### Customizing how recovery codes are generated

The default `RecoveryCodeProvider` generates a 16-digit random string with dashes after every 4 characters and capitalizes the whole string. The recovery codes will be in the format `XXXX-XXXX-XXXX-XXXX` by default. You can use the `generateCodesUsing()` method on the `RecoveryCodeProvider` instance when defining multi-factor authentication on the plugin to change how recovery codes are generated.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\RecoveryCodeProvider;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication(providers: [
        // ...
    ], recoveryProvider: RecoveryCodeProvider::make()
        ->generateCodesUsing(fn () => Str::random(8))
    )
```

> {note} The `RecoveryCodeProvider` hashes each recovery code before it saves them to the user. You should extend or create your own recovery provider if this is undesired behavior.

### Disabling Recovery

Although not recommended, if you do not wish to provide a recovery mechanism for your users, you may pass `false` as a value for the `recoveryProvider` parameter in the `multiFactorAuthentication()` method on the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication(providers: [
        // ...
    ], recoveryProvider: false)
```

Alternatively you could also set the Recovery provider instance to `null` using the `multiFactorRecovery()` method on the plugin **after** you call the `multiFactorAuthentication()` method.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication(providers: [
        // ...
    ])
    ->multiFactorRecovery(null)
```

## Requiring multi-factor authentication

By default, users are not required to set up multi-factor authentication. You can require users to configure it by passing `isRequired: true` as a parameter to the `multiFactorAuthentication()` method on the plugin.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        // ...
    ], isRequired: true)
```

When this is enabled, users will be prompted to set up multi-factor authentication after they sign in, if they have not already done so.

## Customizing the MultiFactorChallenge

The `MultiFactorChallenge` page behaves very similarly to the Login page. Like the [login process](/docs/profile-filament-plugin/{version}/auth/login), we utilize Laravel's [Pipeline](https://laravel.com/docs/13.x/helpers#pipeline) to send a custom `MultiFactorEventBag` object through a series of actions that help finish the multi-factor authentication process. We have a set of default classes that the plugin provides to handle this; however, you may wish to define your own multi-factor authentication process.

To do this, use the `sendMultiFactorChallengeThrough()` method on the plugin instance.

```php
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use App\Actions\Auth\Login\AuthenticateUser;

ProfileFilamentPlugin::make()
    ->multiFactorAuthentication([
        // ...
    ])
    ->sendMultiFactorChallengeThrough([
        AuthenticateUser::class,
    ])
```

Your action classes should have either a `handle()` or `__invoke()` method for the pipeline to interact with. Here is a basic example to get you started:

```php
namespace App\Actions\Auth\Login;

use Closure;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\Dto\MultiFactorEventBagContract;

class AuthenticateUser
{
    public function __invoke(MultiFactorEventBagContract $request, Closure $next)
    {
        // $data = $request->getData();
    
        return $next($request);
    }
}
```

> {note} The multi-factor authentication providers will have already verified the user's identity during form validation, so there is no need for you to handle that in any of your authentication classes.

Here are the default multi-factor authentication actions that we send the MFA challenge request through:

```php
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\PrepareAuthenticatedSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\ChallengePipes\AuthenticateUser;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\ChallengePipes\GuardAgainstExpiredPasswordConfirmation;

$defaults = [
    GuardAgainstExpiredPasswordConfirmation::class,
    AuthenticateUser::class,
    PrepareAuthenticatedSession::class,
];
```

> {tip} With the default authentication classes, we perform the [same authentication check](/docs/profile-filament-plugin/{version}/auth/login#user-content-auth-attempt-callback) on the user as we do in the login form to ensure the user is actually allowed to sign in to the application. 

## Security notes about multi-factor authentication

Similar to how Filament handles MFA, the plugin's multi-factor authentication process occurs before the user is actually authenticated into the app. This allows you to be sure that no users can authenticate and access the app without passing the multi-factor authentication step. You do not need to remember to add middleware to any of your authenticated routes to ensure that users completed the multi-factor authentication step.

However, if you have other parts of your Laravel app that authenticate users, be aware that they will not be challenged for multi-factor authentication if they are already authenticated elsewhere and visit the panel, unless [multi-factor authentication is required](#user-content-requiring-multi-factor-authentication) and they have not set it up.
