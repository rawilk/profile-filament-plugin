<?php

declare(strict_types=1);

return [
    'heading' => 'Two-factor authentication',

    'description' => 'Two-factor authentication adds an additional layer of security to your account by requiring more than just a password to sign in. Configure any of the two-factor providers below to enable two-factor authentication on your account.',

    'messages' => [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
    ],

    'preferred-mfa-provider' => [
        'label' => 'Preferred two-factor provider',
        'description' => 'Set your preferred two-factor provider to use when authenticating.',
        'placeholder' => 'No preference',

        'notifications' => [
            'saved' => [
                'title' => 'Two-factor provider preference saved.',
            ],
        ],
    ],
];
