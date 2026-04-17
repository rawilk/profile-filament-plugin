<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Recovery codes',
        'description' => 'Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.',

        'messages' => [
            'codes-remaining' => '1 code remaining|:count codes remaining',
        ],
    ],

    'challenge-form' => [
        'code' => [
            'label' => 'Enter one of your recovery codes',
            'validation-attribute' => 'recovery code',

            'messages' => [
                'invalid' => 'The recovery code you entered is invalid.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Verify Account',
            ],

            'change-provider' => [
                'label' => 'Recovery Code',
            ],
        ],
    ],
];
