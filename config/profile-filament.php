<?php

declare(strict_types=1);

return [
    'component_defaults' => [
        'nav_item' => [
            'color' => 'primary',
        ],
    ],

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
    | Here you may define which action classes should be used for various
    | actions.
    |
    */
    'actions' => [
        'update_password' => \Rawilk\ProfileFilament\Actions\UpdatePasswordAction::class,

        // General two factor
        'disable_two_factor' => \Rawilk\ProfileFilament\Actions\TwoFactor\DisableTwoFactorAction::class,
        'generate_new_recovery_codes' => \Rawilk\ProfileFilament\Actions\TwoFactor\GenerateNewRecoveryCodesAction::class,
        'mark_two_factor_disabled' => \Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorDisabledAction::class,
        'mark_two_factor_enabled' => \Rawilk\ProfileFilament\Actions\TwoFactor\MarkTwoFactorEnabledAction::class,

        // Authenticator apps
        'confirm_authenticator_app' => \Rawilk\ProfileFilament\Actions\AuthenticatorApps\ConfirmTwoFactorAppAction::class,
        'delete_authenticator_app' => \Rawilk\ProfileFilament\Actions\AuthenticatorApps\DeleteTwoFactorAppAction::class,

        // Webauthn
        'delete_webauthn_key' => \Rawilk\ProfileFilament\Actions\Webauthn\DeleteWebauthnKeyAction::class,
        'register_webauthn_key' => \Rawilk\ProfileFilament\Actions\Webauthn\RegisterWebauthnKeyAction::class,

        // Passkeys
        'delete_passkey' => \Rawilk\ProfileFilament\Actions\Passkeys\DeletePasskeyAction::class,
        'register_passkey' => \Rawilk\ProfileFilament\Actions\Passkeys\RegisterPasskeyAction::class,
        'upgrade_to_passkey' => \Rawilk\ProfileFilament\Actions\Passkeys\UpgradeToPasskeyAction::class,
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Here you may override the models provided by this package.
    |
    */
    'models' => [
        /**
         * Authenticator App
         *
         * This model is responsible for storing a user's verified authenticator apps
         * for 2fa. Your model must extend the AuthenticatorApp model.
         */
        'authenticator_app' => \Rawilk\ProfileFilament\Models\AuthenticatorApp::class,

        /**
         * Webauthn Key
         *
         * This model is responsible for storing webauthn keys for a user, such
         * as hardware security keys or passkeys. Your model must extend
         * the WebauthnKey model.
         */
        'webauthn_key' => \Rawilk\ProfileFilament\Models\WebauthnKey::class,
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
        'authenticator_app' => \Rawilk\ProfileFilament\Policies\AuthenticatorAppPolicy::class,
        'webauthn_key' => \Rawilk\ProfileFilament\Policies\WebauthnKeyPolicy::class,
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
            'id' => env('WEBAUTHN_RELYING_PARTY_ID', env('APP_URL')),

            // Image must be encoded as base64.
            'icon' => env('WEBAUTHN_RELYING_PARTY_ICON'),
        ],

        /**
         * Attestation conveyance. This specifies the preference regarding the attestation
         * conveyance during credential generation.
         *
         * This shouldn't need to be changed in most cases.
         */
        'attestation_conveyance' => env('WEBAUTHN_ATTESTATION_CONVEYANCE', \Webauthn\PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE),

        /**
         * You can indicate if the authenticator must be attached to the client (platform authenticator i.e.
         * it is usually not removable from the client device) or must be detached (roaming authenticator).
         *
         * By default, we'll allow for both platform (passkeys included), and cross-platform (hardware security keys).
         */
        'authenticator_attachment' => env('WEBAUTHN_AUTHENTICATOR_ATTACHMENT', \Webauthn\AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE),

        /**
         * You can indicate the user verification requirements (such as entering a PIN on a security key) during
         * the ceremonies with this value.
         *
         * By default, we use the default value used by Webauthn, which is "preferred". You can change this value
         * to be either "discouraged" or "required" as well.
         */
        'user_verification' => env('WEBAUTHN_USER_VERIFICATION', \Webauthn\AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED),

        /**
         * With this criterion, a Public Key Credential Source will be stored in the authenticator,
         * client or client device. Such storage requires an authenticator capable to store
         * such a resident credential.
         *
         * Note: When set to "required" or "preferred", user verification will
         * always be required.
         */
        'resident_key' => env('WEBAUTHN_RESIDENT_KEY', \Webauthn\AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED),

        /**
         * Timeout - the time that the caller is willing to wait for the call to complete.
         *
         * If the user verification is "discouraged", timeout should be between 30 and 180 seconds.
         * If the user verification is "preferred" or "required", the range is 300 to 600 seconds (5 to 10 minutes)
         *
         * Default timeout is 60 seconds (60,000 milliseconds)
         */
        'timeout' => 60_000,

        /**
         * Passkey timeout.
         *
         * Since passkeys require user verification, the timeout should be
         * between 300 and 600 seconds (5 to 10 minutes).
         *
         * We will stick with a default, lower-end timeout of 300 seconds (300,000 milliseconds).
         */
        'passkey_timeout' => 300_000,

        /**
         * Enable logging when webauthn attestation or assertion requests are made.
         */
        'logging_enabled' => env('WEBAUTHN_LOGGING_ENABLED', env('APP_ENV') === 'local'),
    ],
];
