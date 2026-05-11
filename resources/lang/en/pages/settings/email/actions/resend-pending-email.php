<?php

declare(strict_types=1);

return [
    'label' => 'Resend email',

    'messages' => [
        'throttled' => [
            'title' => 'Too many requests',
            'body' => 'Please try again in :minutes minutes.',
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Success!',
            'body' => 'A new verification link has been sent to your new email address.',
        ],
    ],
];
