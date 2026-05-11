<?php

declare(strict_types=1);

return [
    'label' => 'Edit',

    'modal' => [
        'heading' => 'Edit your information',

        'actions' => [
            'submit' => [
                'label' => 'Save',
            ],
        ],

        'form' => [
            'name' => [
                'label' => 'Your name',
                'validation-attribute' => 'name',
            ],
        ],
    ],

    'notifications' => [
        'saved' => [
            'title' => 'Your profile information has been updated!',
        ],
    ],
];
