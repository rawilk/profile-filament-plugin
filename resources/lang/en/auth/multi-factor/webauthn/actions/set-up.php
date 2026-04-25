<?php

declare(strict_types=1);

return [
    'label' => 'Set up',
    'another-label' => 'Set up another',

    'modal' => [
        'heading' => 'Create a passkey for your account',

        'description' => 'Create and register a passkey to enhance your account security during authentication.',

        'form' => [
            'name' => [
                'label' => 'Key name',

                'validation-attribute' => 'name',

                'placeholder' => '1Password',

                'default-name' => 'Security key',
            ],
        ],

        'actions' => [
            'register' => [
                'label' => 'Start key registration',
            ],
        ],
    ],

    'messages' => [
        'failed' => 'Security key registration failed',

        'throttled' => [
            'title' => 'Too many attempts',
            'body' => 'Please try again in :seconds seconds.',
        ],
    ],

    'notifications' => [
        'enabled' => [
            'title' => 'Security key was created successfully.',
        ],
    ],
];
