<?php

declare(strict_types=1);

return [
    'heading' => 'Change password',

    'form' => [
        'password' => [
            'label' => 'New password',
            'validation-attribute' => 'password',
        ],

        'password-confirmation' => [
            'label' => 'Confirm new password',
            'validation-attribute' => 'password confirmation',
        ],

        'current-password' => [
            'label' => 'Current password',
            'validation-attribute' => 'current password',
            'below-content' => 'For security, please confirm your password to continue.',
        ],
    ],

    'actions' => [
        'save' => [
            'label' => 'Update password',
        ],

        'forgot-password' => [
            'label' => 'I forgot my password',
        ],
    ],

    'notifications' => [
        'saved' => [
            'title' => 'Password updated!',
        ],

        'throttled' => [
            'title' => 'Too many requests.',
            'body' => 'Please try again in :seconds seconds.',
        ],
    ],
];
