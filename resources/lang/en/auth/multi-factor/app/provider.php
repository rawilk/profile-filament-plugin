<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Authenticator app',
        'description' => 'Use an authentication app or browser extension to get two-factor authentication codes when prompted.',

        'messages' => [
            'configured' => 'Configured',
        ],

        'list' => [
            'toggle-list' => '1 authenticator app configured|:count authenticator apps configured',
        ],
    ],

    'challenge-form' => [
        'code' => [
            'label' => 'Enter the 6-digit code from your authenticator app',
            'validation-attribute' => 'code',

            'messages' => [
                'invalid' => 'The code you entered is invalid.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Verify Account',
            ],

            'change-provider' => [
                'label' => 'Authenticator App',
            ],
        ],
    ],
];
