<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions as WebauthnActions;

return [
    /*
    |--------------------------------------------------------------------------
    | Hash User Passwords
    |--------------------------------------------------------------------------
    |
    | By default, we will assume your user model is using the `hashed` cast
    | for your passwords. If not, set this value to true, so we can hash
    | before updating it on the user.
    |
    */
    'hash_user_passwords' => false,

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    |
    | The plugin performs several actions that may be customized here. Any
    | custom action classes should implement the interface of the plugin
    | action you are replacing.
    |
    */
    'actions' => [
        'update_password' => Rawilk\ProfileFilament\Actions\UpdatePasswordAction::class,
        'delete_account' => Rawilk\ProfileFilament\Actions\DeleteAccountAction::class,

        // General multi-factor
        'mark_multifactor_disabled' => Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorDisabledAction::class,
        'mark_multifactor_enabled' => Rawilk\ProfileFilament\Auth\Multifactor\Actions\MarkMultiFactorEnabledAction::class,

        // Authenticator apps
        'delete_authenticator_app' => Rawilk\ProfileFilament\Auth\Multifactor\App\Actions\DeleteAuthenticatorAppAction::class,
        'store_authenticator_app' => Rawilk\ProfileFilament\Auth\Multifactor\App\Actions\StoreAuthenticatorAppAction::class,

        // Email authentication
        'enable_email_authentication' => Rawilk\ProfileFilament\Auth\Multifactor\Email\Actions\EnableEmailAuthenticationAction::class,
        'disable_email_authentication' => Rawilk\ProfileFilament\Auth\Multifactor\Email\Actions\DisableEmailAuthenticationAction::class,

        // Pending user emails
        'update_user_email' => Rawilk\ProfileFilament\Actions\PendingUserEmails\UpdateUserEmailAction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Here you may define which table names should be used for the package's
    | database tables.
    |
    */
    'table_names' => [
        'authenticator_app' => 'authenticator_apps',
        'webauthn_key' => 'webauthn_keys',
        'pending_user_email' => 'pending_user_emails',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Here you may override the models provided by this package.
    |
    | Note: Any custom models you define MUST extend the package's models.
    |
    */
    'models' => [
        /**
         * Authenticator App
         *
         * This model is responsible for storing a user's verified authenticator apps
         * for 2fa.
         */
        'authenticator_app' => Rawilk\ProfileFilament\Models\AuthenticatorApp::class,

        /**
         * Webauthn Key
         *
         * This model is responsible for storing webauthn keys for a user, such
         * as hardware security keys or passkeys.
         */
        'webauthn_key' => Rawilk\ProfileFilament\Models\WebauthnKey::class,

        /**
         * Pending User Email
         *
         * This model is responsible for storing tokens for when a user wants to
         * change their email address.
         */
        'pending_user_email' => Rawilk\ProfileFilament\Models\PendingUserEmail::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Policies
    |--------------------------------------------------------------------------
    |
    | We provide basic policies for the models in this package, however you
    | are free to override them and use your own policies.
    |
    */
    'policies' => [
        'authenticator_app' => Rawilk\ProfileFilament\Policies\AuthenticatorAppPolicy::class,
        'webauthn_key' => Rawilk\ProfileFilament\Policies\WebauthnKeyPolicy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sudo mode
    |--------------------------------------------------------------------------
    |
    | Here you may define how long the "sudo" mode should last when performing
    | sensitive actions, such as a user modifying their account settings.
    |
    | After you authenticate to perform a sensitive action, your session is
    | temporarily in "sudo" mode. In sudo mode, you can perform sensitive
    | actions without authentication, until the sudo mode expires. Any
    | sensitive action performed while in sudo mode will reset the timer.
    |
    */
    'sudo' => [
        'expires' => DateInterval::createFromDateString('2 hours'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webauthn
    |--------------------------------------------------------------------------
    |
    | Here are some webauthn specific settings you may set. We've set some
    | sensible defaults that should work in most cases.
    |
    */
    'webauthn' => [
        'relying_party' => [
            'name' => env('WEBAUTHN_RELYING_PARTY_NAME', env('APP_NAME')),
            'id' => env('WEBAUTHN_RELYING_PARTY_ID', parse_url(config('app.url'), PHP_URL_HOST)),

            // Image must be encoded as base64.
            'icon' => env('WEBAUTHN_RELYING_PARTY_ICON'),
        ],

        /*
         * These actions are responsible for performing core tasks regarding webauthn.
         * You can customize them by creating a class that extends the default, and
         * by specifying your custom class name here.
         */
        'actions' => [
            'configure_ceremony_step_manager_factory' => WebauthnActions\ConfigureCeremonyStepManagerFactoryAction::class,
            'delete_security_key' => WebauthnActions\DeleteSecurityKeyAction::class,
            'find_security_key_to_authenticate' => WebauthnActions\FindSecurityKeyToAuthenticateAction::class,
            'generate_security_key_authentication_options' => WebauthnActions\GenerateSecurityKeyAuthenticationOptionsAction::class,
            'generate_security_key_registration_options' => WebauthnActions\GenerateSecurityKeyRegistrationOptionsAction::class,
            'store_security_key' => WebauthnActions\StoreSecurityKeyAction::class,
        ],
    ],
];
