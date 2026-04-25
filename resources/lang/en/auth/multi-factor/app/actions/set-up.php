<?php

declare(strict_types=1);

return [
    'label' => 'Set up',
    'another-label' => 'Set up another',

    'modal' => [
        'heading' => 'Set up authenticator app',

        'description' => <<<'BLADE'
        You'll need an authenticator app or browser extension like <x-filament::link href="https://support.1password.com/one-time-passwords/" target="_blank">1Password</x-filament::link> or Google Authenticator (<x-filament::link href="https://itunes.apple.com/us/app/google-authenticator/id388497605" target="_blank">iOS</x-filament::link>, <x-filament::link href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</x-filament::link>) to complete this process.
        BLADE,

        'content' => [
            'qr-code' => [
                'title' => 'Scan QR code',
                'instruction' => 'Scan the QR code below or manually enter the secret key into your authenticator app.',
                'alt' => 'QR code to scan with an authenticator app',
            ],

            'text-code' => [
                'title' => "Can't scan QR code?",
                'instruction' => 'Enter this secret instead:',

                'actions' => [
                    'copy' => [
                        'label' => 'Copy code',
                        'copied' => 'Copied',
                    ],
                ],
            ],
        ],

        'form' => [
            'steps' => [
                'app' => [
                    'label' => 'App Registration',
                ],

                'name' => [
                    'label' => 'Name',
                ],
            ],

            'code' => [
                'title' => 'Get verification code',
                'instruction' => 'Enter the 6-digit code you see in your authenticator app.',

                'label' => 'Enter verification code',

                'validation-attribute' => 'code',

                'below-content' => 'You will need to enter the 6-digit code from your authenticator app each time you sign in or perform sensitive actions.',

                'messages' => [
                    'invalid' => 'The code you entered is invalid.',
                    'throttled' => 'Too many attempts. Please try again later.',
                ],
            ],

            'name' => [
                'label' => 'App name',

                'help' => 'You may give the app a meaningful name so you can identify it later.',

                'placeholder' => '1Password',

                'default-name' => 'Authenticator app',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Enable authenticator app',
            ],
        ],
    ],

    'notifications' => [
        'enabled' => [
            'title' => 'Authenticator app has been enabled.',
        ],
    ],
];
