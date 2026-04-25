<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Passkeys and security keys',

        'description' => 'With passkeys, you can securely sign in to your account using just your fingerprint, face, screen lock, or a secure key stored in a password manager. Passkeys can also be used as a second step when signing in with your password.',

        'select-label' => 'Passkeys or security keys',

        'messages' => [
            'configured' => 'Configured',
            'not-passkey' => 'This key can only be used with a password.',
        ],

        'list' => [
            'toggle-list' => '1 security key configured|:count security keys configured',
        ],
    ],

    'messages' => [
        'unsupported' => [
            'title' => "Your browser isn't supported!",
            'body' => 'It appears your browser or device is not compatible with WebAuthn security keys. You can either use one of your other multi-factor methods, or try switching to a supported browser.',
            'learn-more-link' => 'Learn more',
        ],

        'waiting-for-input' => 'Waiting for input from browser interaction...',
    ],

    'challenge-form' => [
        'form' => [
            'prompt' => [
                'label' => 'Verify your identity with your passkey or security key.',
            ],
        ],

        'messages' => [
            'failed' => 'Passkey authentication failed or timed out. Please try again.',
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Use passkey or security key',
            ],

            'change-provider' => [
                'label' => 'Passkey or security key',
            ],
        ],
    ],
];
