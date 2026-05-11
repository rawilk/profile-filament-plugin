<?php

declare(strict_types=1);

return [
    'label' => 'Logout all other devices',

    'modal' => [
        'heading' => 'Logout all other devices',

        'form' => [
            'password' => [
                'label' => 'Your password',
                'validation-attribute' => 'password',
                'help' => 'Your password is required to force a logout of sessions that may have the remember cookie set on them.',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Logout all other devices',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'All other devices have been logged out.',
        ],
    ],
];
