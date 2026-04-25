<?php

declare(strict_types=1);

return [
    'challenge' => [
        'heading' => 'Passkey or security key',

        'form' => [
            'prompt' => [
                'label' => 'When you are ready, authenticate using the button below.',
            ],
        ],

        'messages' => [
            'failed' => 'Passkey authentication failed or timed out. Please try again.',
        ],

        'actions' => [
            'generate-options' => [
                'label' => 'Use passkey or security key',
            ],

            'change-to' => [
                'label' => 'Use your passkey or security key',
            ],
        ],
    ],
];
