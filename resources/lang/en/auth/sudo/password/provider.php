<?php

declare(strict_types=1);

return [
    'challenge' => [
        'form' => [
            'password' => [
                'label' => 'Your password',
                'validation-attribute' => 'password',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Confirm',
            ],

            'change-to' => [
                'label' => 'Use your password',
            ],
        ],
    ],
];
