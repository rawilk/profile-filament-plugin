<?php

declare(strict_types=1);

return [
    'title' => 'Password and authentication',

    'password' => [
        'title' => 'Change password',

        'form' => [
            'current_password' => 'Current password',
            'password' => 'New password',
            'password_confirmation' => 'Confirm new password',
            'save_button' => 'Update password',
            'notification' => 'Password updated!',
            'forgot_password_link' => 'I forgot my password',
            'form_info' => 'Note: Changing your password will log you out of all your other devices.',
        ],
    ],

    'mfa' => [
        'title' => 'Two-factor authentication',
        'status_enabled' => 'Enabled',
        'status_disabled' => 'Inactive',
        'description' => 'Two-factor authentication adds an additional layer of security to your account by requiring more than just a password to sign in. To enable two-factor authentication on your account, add one or more of the two-factor methods below.',
        'methods_title' => 'Two-factor methods',
        'recovery_title' => 'Recovery options',
        'method_configured' => 'Configured',
        'method_registration_date' => '— registered on :date',
        'method_last_used_date' => 'Last used: :date',
        'method_never_used' => 'Never',

        'app' => [
            'title' => 'Authenticator app',
            'description' => 'Use an authentication app or browser extension to get two-factor authentication codes when prompted.',
            'device_count' => ':count app|:count apps',
            'form_intro' => 'Authenticator apps and browser extensions like [1Password](:one_password), [Authy](:authy), [Microsoft Authenticator](:microsoft), etc. generate one-time passwords that are used as a second factor to verify your identity when prompted during sign-in.',
            'scan_title' => 'Scan the QR code',
            'scan_instructions' => 'Use an authenticator app or browser extension to scan the QR code below.',
            'enter_code_instructions' => 'If you are not able to scan the QR code, you can manually enter your secret key into your authenticator app.',
            'code_confirmation_input' => 'Verify the code from the app',
            'code_confirmation_placeholder' => '6-digit code',
            'device_name' => 'Device name',
            'device_name_help' => 'You may give the app a meaningful name so you can identify it later.',
            'device_name_placeholder' => 'Authy',
            'default_device_name' => 'Authenticator app',
            'code_verification_fail' => 'Two-factor code verification failed. Please try again.',
            'code_verification_pass' => 'Two-factor code verification was successful.',
            'copy_secret_tooltip' => 'Copy secret to clipboard',
            'copy_secret_confirmation' => 'Copied',
            'submit_code_confirmation' => 'Save',
            'cancel_code_confirmation' => 'Cancel',
            'add_button' => 'Add',
            'add_another_app_button' => 'Register new app',
            'show_button' => 'Edit',
            'hide_button' => 'Hide',

            'actions' => [

                'delete' => [
                    'trigger_tooltip' => 'Remove app',
                    'trigger_label' => 'Delete :name',
                    'title' => 'Delete Authenticator App',
                    'confirm' => 'Delete',
                    'description' => 'You will no longer be able to use the **:name** app as a second form of authentication.',
                    'success_message' => 'Authenticator app ":name" deleted.',
                ],

                'edit' => [
                    'trigger_tooltip' => 'Edit app name',
                    'trigger_label' => 'Edit :name',
                    'title' => 'Edit Authenticator App',
                    'name' => 'Device name',
                    'name_help' => 'You may give the app a meaningful name so you can identify it later.',
                    'success_message' => 'Authenticator app was updated successfully.',
                ],

            ],
        ],

        'webauthn' => [
            'title' => 'Security keys',
            'description' => 'Security keys are hardware devices that can be used as your second factor of authentication.',
            'device_count' => ':count key|:count keys',
            'add_button' => 'Add',
            'show_button' => 'Edit',
            'hide_button' => 'Hide',

            'actions' => [

                'register' => [
                    'trigger' => 'Register new security key',
                    'name' => 'Key name',
                    'name_placeholder' => 'Enter a nickname for this security key',
                    'prompt_trigger' => 'Add',
                    'register_fail' => 'Security key registration failed.',
                    'retry_button' => 'Try again',
                    'waiting' => 'Waiting for input from browser interaction...',
                    'register_fail_notification' => 'We were unable to register your security key at this time. Please try again with a different device.',
                    'success' => 'Security key as registered successfully.',
                ],

                'delete' => [
                    'trigger_tooltip' => 'Remove security key',
                    'trigger_label' => 'Delete :name',
                    'title' => 'Delete Security Key',
                    'confirm' => 'Delete',
                    'description' => 'You will no longer be able to use the **:name** security key as a second form of authentication.',
                    'success_message' => 'Security key ":name" deleted.',
                ],

                'edit' => [
                    'title' => 'Edit Security Key',
                    'trigger_tooltip' => 'Edit security key name',
                    'trigger_label' => 'Edit :name',
                    'name' => 'Key name',
                    'name_placeholder' => 'Enter a nickname for this security key',
                    'success_message' => 'Security key was updated successfully.',
                ],

            ],
        ],

        'recovery_codes' => [
            'title' => 'Recovery codes',
            'mfa_disabled' => 'You must first add a two-factor method before you can view recovery codes.',
            'description' => 'Recovery codes can be used to access your account in the event you lose access to your device and cannot receive the two-factor authentication codes.',
            'show_button' => 'View',
            'hide_button' => 'Hide',
            'current_codes_title' => 'Your recovery codes',
            'recommendation' => 'Keep your recovery codes as safe as your password. We recommend saving them with a password manager such as [1Password](:1password), [Authy](:authy), or [Keeper](:keeper).',
            'warning' => '**Keep your recovery codes in a safe spot.** These codes are the last resort for accessing your account in case you lose your password and second factors. If you cannot find these codes, you **will** lose access to your account.',
            'regenerated_warning' => '**These new codes have replaced your old codes. Save them in a safe spot.** These codes are the last resort for accessing your account in case you lose your password and second factors. If you cannot find these codes, you **will** lose access to your account.',

            'actions' => [

                'download' => [
                    'label' => 'Download',
                ],

                'print' => [
                    'label' => 'Print',
                    'print_page_description' => ':app_name two-factor authentication account recovery codes.',
                    'print_page_title' => 'Recovery codes',
                ],

                'copy' => [
                    'label' => 'Copy',
                    'confirmation' => 'Copied',
                ],

                'generate' => [
                    'heading' => 'Generate new recovery codes',
                    'description' => "When you generate new recovery codes, you must download or print the new codes. **Your old codes won't work anymore.**",
                    'button' => 'Generate new recovery codes',
                    'success_title' => 'Success!',
                    'success_message' => 'New two-factor recovery codes successfully generated. Save them in a safe, durable location and discard your previous codes.',
                ],

            ],
        ],
    ],

    'passkeys' => [
        'title' => 'Passkeys',
        'empty_heading' => 'Passwordless sign-in with passkeys',
        'empty_description' => "Passkeys are a password replacement that validates your identity using touch, facial recognition, a device password, or a PIN.\n\nPasskeys can be used for sign-in as a simple and secure alternative to your password and two-factor credentials.",
        'default_key_name' => 'Passkey',
        'unique_validation_error' => 'You already have a device with this name.',

        'list' => [
            'title' => 'Your passkeys',
            'description' => 'Passkeys are a password replacement that validates your identity using touch, facial recognition, a device password, or a PIN.',
        ],

        'actions' => [

            'add' => [
                'trigger' => 'Add a passkey',
                'modal_title' => 'Configure passwordless authentication',
                'intro' => 'Your device supports passkeys, a password replacement that validates your identity using touch, facial recognition, a device password, or a PIN.',
                'intro_line2' => 'Passkeys can be used for sign-in as a simple and secure alternative to your password and two-factor credentials.',
                'prompt_button' => 'Add passkey',
                'register_fail' => 'Passkey registration failed.',
                'register_fail_notification' => 'We were unable to register your passkey at this time. Please try again later.',
                'name_field' => 'Passkey nickname',
                'name_field_placeholder' => 'iPhone',
                'mfa_disabled_notice' => '**Note:** Adding a passkey will also enable two-factor authentication via recovery codes on your account if you ever lose access to your passkey.',

                'success' => [
                    'title' => 'Passkey registration successful',
                    'description' => 'From now on, you can use this passkey to sign-in to :app_name.',
                ],
            ],

            'edit' => [
                'trigger_label' => 'Edit :name',
                'trigger_tooltip' => 'Edit passkey nickname',
                'title' => 'Edit passkey',
                'name' => 'Passkey nickname',
                'name_placeholder' => 'iPhone',
                'success_notification' => 'Passkey was updated successfully!',
            ],

            'delete' => [
                'trigger_label' => 'Delete :name',
                'trigger_tooltip' => 'Delete passkey',
                'title' => 'Delete passkey',
                'confirm' => 'Delete',
                'description' => "Are you sure you want to delete your \`**:name**\` passkey?\n\nBy removing this passkey you will no longer be able to use it to sign-in to your account from any of the devices on which it has been synced.\n\n**Note:** You may continue to see this passkey as an option during sign-in until you also delete it from your browser, device or associated account's password management settings.",
                'success_message' => 'Passkey ":name" was deleted',
            ],

            'upgrade' => [
                'trigger_label' => 'Upgrade :name to a passkey',
                'trigger_tooltip' => 'Upgrade to passkey',
                'modal_title' => 'Upgrade your security key registration to a passkey',
                'intro' => 'Your security key **\`:name\`** can be upgrade to a passkey.',
                'prompt_button' => 'Upgrade to passkey',
                'cancel_upgrade' => 'Register a different passkey',

                'success' => [
                    'title' => "Successfully upgraded ':name' to a passkey",
                    'description' => "From now on, you can use this passkey to sign-in to :app_name. We've deleted the old ':name' security key.",
                ],
            ],

        ],
    ],
];
