<?php

declare(strict_types=1);

return [
    'label' => 'Logout device',

    'modal' => [
        'heading' => 'Logout device',

        'form' => [
            'password' => [
                'label' => 'Your password',
                'validation-attribute' => 'password',
                'help' => 'Your password is required to force a logout of sessions that may have the remember cookie set on them.',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Logout device',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'The device was logged out.',
        ],
    ],
];
