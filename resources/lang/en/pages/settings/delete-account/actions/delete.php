<?php

declare(strict_types=1);

return [
    'label' => 'Delete your account',

    'modal' => [
        'heading' => 'Delete your account',

        'form' => [
            'email' => [
                'label' => 'To confirm, type your email, ":email", in the box below:',

                'validation-attribute' => 'email',

                'messages' => [
                    'incorrect' => 'The email address you entered is not correct.',
                ],
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Delete your account',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Your account has been deleted.',
        ],
    ],
];
