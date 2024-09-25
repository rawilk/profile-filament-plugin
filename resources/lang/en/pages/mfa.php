<?php

declare(strict_types=1);

return [
    // Fallback page title
    'heading' => 'Two-factor authentication',

    // Fallback alternatives heading
    'alternative_heading' => 'Having problems?',

    'totp' => [
        'heading' => 'Two-factor authentication',
        'label' => 'Authentication code',
        'placeholder' => '6-digit code',
        'hint' => 'Open your two-factor authenticator (TOTP) app or browser extension to view your authentication code.',
        'alternative_heading' => 'Unable to verify with your authenticator app?',
        'use_label' => 'Use your authenticator app',
        'invalid' => 'The code you entered is not valid.',
    ],

    'recovery_code' => [
        'heading' => 'Two-factor recovery',
        'label' => 'Recovery code',
        'placeholder' => 'XXXXX-XXXXX',
        'hint' => 'If you are unable to access your mobile device, enter one of your recovery codes to verify your identity.',
        'alternative_heading' => "Don't have a recovery code?",
        'use_label' => 'Use a recovery code',
        'invalid' => 'The code you entered is not valid.',
    ],

    'webauthn' => [
        'heading' => 'Two-factor authentication',
        'label' => 'Security key',
        'label_including_passkeys' => 'Passkey or security key',
        'hint' => 'When you are ready, authenticate using the button below.',
        'alternative_heading' => 'Unable to verify with your security key?',
        'use_label' => 'Use your security key',
        'use_label_including_passkeys' => 'Use your passkey or security key',
        'waiting' => 'Waiting for input from browser interaction...',
        'failed' => 'Authentication failed.',
        'retry' => 'Retry security key',
        'retry_including_passkeys' => 'Retry passkey or security key',
        'passkey_login_button' => 'Sign in with a passkey',

        'assert' => [
            'failure_title' => 'Error',
            'failure' => 'We were unable to verify your identity with this key. Please try a different key or a different form of authentication to verify your identity.',
            'passkey_required' => 'This key cannot be used for passkey authentication.',
        ],

        'unsupported' => [
            'title' => "Your browser isn't supported!",
            'message' => 'It appears your browser or device is not compatible with WebAuthn security keys. You can either use one of your other two-factor methods, or try switching to a supported browser.',
            'learn_more_link' => 'Learn more',
        ],
    ],

    'actions' => [
        'authenticate' => 'Verify',
        'webauthn' => 'Use security key',
        'webauthn_including_passkeys' => 'Use passkey or security key',
    ],

];
