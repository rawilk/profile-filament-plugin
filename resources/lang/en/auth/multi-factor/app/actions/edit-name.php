<?php

declare(strict_types=1);

return [
    'label' => 'Edit `:name`',
    'tooltip' => 'Edit app name',

    'modal' => [
        'heading' => 'Edit Authenticator App',

        'actions' => [
            'submit' => [
                'label' => 'Save',
            ],
        ],
    ],

    'notifications' => [
        'updated' => [
            'title' => 'Authenticator app was updated successfully.',
        ],
    ],
];
