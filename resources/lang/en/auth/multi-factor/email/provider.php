<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Email verification codes',
        'description' => 'Receive a temporary code at your email address to verify your identity during authentication requests.',

        'select-label' => 'Email verification codes',

        'messages' => [
            'disabled' => 'Disabled',
            'enabled' => 'Enabled',
        ],
    ],

    'challenge-form' => [
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

            'problems' => [
                'title' => "Didn't receive a code?",
                'description' => 'It may take a few moments for you to receive it. Check your spam folder for an email from us. Otherwise, you can request a new code.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Confirm',
            ],

            'change-provider' => [
                'label' => 'Email Verification Code',
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
