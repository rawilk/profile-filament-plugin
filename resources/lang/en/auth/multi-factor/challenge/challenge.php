<?php

declare(strict_types=1);

return [
    'title' => '2-Step Verification',

    'heading' => 'Verify Your Identity',

    'subheading' => "To keep your account safe, we want to make sure it's really you trying to sign in.",

    'form' => [
        'provider' => [
            'heading' => 'Choose how you want to verify your identity:',
        ],
    ],

    'actions' => [
        'change-provider' => [
            'label' => 'Try another way',
        ],
    ],

    'messages' => [
        'password-confirmation-expired' => 'Please confirm your password again to resume multi-factor authentication.',
    ],
];
