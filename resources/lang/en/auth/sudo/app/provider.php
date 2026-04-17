<?php

declare(strict_types=1);

return [
    'challenge' => [
        'heading' => 'Authentication code',

        'form' => [
            'code' => [
                'label' => 'Enter the 6-digit code from your authenticator app',
                'validation-attribute' => 'code',

                'messages' => [
                    'invalid' => 'The code you entered is invalid.',
                ],
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Verify',
            ],

            'change-to' => [
                'label' => 'Use your authenticator app',
            ],
        ],
    ],
];
