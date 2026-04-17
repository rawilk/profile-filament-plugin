<?php

declare(strict_types=1);

return [
    'label' => 'Turn off',

    'modal' => [
        'heading' => 'Disable email verification codes',

        'description' => 'Are you sure you want to stop receiving email verification codes? Disabling this will remove an extra layer of security from your account.',

        'actions' => [
            'submit' => [
                'label' => 'Disable',
            ],
        ],
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'Email verification codes have been disabled.',
        ],
    ],
];
