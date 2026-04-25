<?php

declare(strict_types=1);

return [
    'label' => 'Edit `:name`',
    'tooltip' => 'Edit security key name',

    'modal' => [
        'heading' => 'Edit Security Key',

        'actions' => [
            'submit' => [
                'label' => 'Save',
            ],
        ],
    ],

    'notifications' => [
        'updated' => [
            'title' => 'Security key was updated successfully.',
        ],
    ],
];
