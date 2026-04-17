<?php

declare(strict_types=1);

return [
    'challenge' => [
        'heading' => 'Email verification code',

        'form' => [
            'details' => [
                'label' => 'We sent your verification code to <strong>:email</strong>.',
            ],

            'code' => [
                'label' => 'Enter your 6-digit code',
                'help' => 'Your code expires in :minutes minutes.',
                'placeholder' => 'XXXXXX',
                'validation-attribute' => 'code',

                'messages' => [
                    'invalid' => 'The code you entered is invalid.',
                ],
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Confirm',
            ],

            'change-to' => [
                'label' => 'Use an email verification code',
            ],

            'resend-code' => [
                'label' => 'Request a new code',

                'notifications' => [
                    'resent' => [
                        'title' => "We've sent you a new code by email.",
                    ],
                ],
            ],
        ],

        'notifications' => [
            'throttled' => [
                'title' => 'Too many requests',
                'body' => 'Please try again in :seconds seconds.',
            ],
        ],
    ],
];
