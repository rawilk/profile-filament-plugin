<?php

declare(strict_types=1);

return [
    'label' => 'Change email',

    'modal' => [
        'heading' => 'Edit email address',

        'form' => [
            'email' => [
                'label' => 'New email address',
                'validation-attribute' => 'email',
                'placeholder' => 'example@:host',
                'help' => 'We will send an email to this address to verify you have access to it. Your changes will not take effect until you verify the new email address.',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Update email',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Success!',
            'body' => 'Your email address has been updated.',
            'body-pending' => 'Check your new email address for a verification link.',
        ],
    ],
];
