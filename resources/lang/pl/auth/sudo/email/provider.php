<?php

declare(strict_types=1);

return [
    'challenge' => [
        'heading' => 'E-mailowy kod weryfikacyjny',

        'form' => [
            'details' => [
                'label' => 'Wysłaliśmy Twój kod weryfikacyjny na adres <strong>:email</strong>.',
            ],

            'code' => [
                'label' => 'Wprowadź swój 6-cyfrowy kod',
                'help' => 'Twój kod wygaśnie za :minutes minut.',
                'placeholder' => 'XXXXXX',
                'validation-attribute' => 'kod',

                'messages' => [
                    'invalid' => 'Wprowadzony kod jest nieprawidłowy.',
                ],
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Potwierdź',
            ],

            'change-to' => [
                'label' => 'Użyj e-mailowego kodu weryfikacyjnego',
            ],

            'resend-code' => [
                'label' => 'Poproś o nowy kod',

                'notifications' => [
                    'resent' => [
                        'title' => 'Wysłaliśmy Ci nowy kod e-mailem.',
                    ],
                ],
            ],
        ],

        'notifications' => [
            'throttled' => [
                'title' => 'Zbyt wiele żądań',
                'body' => 'Spróbuj ponownie za :seconds sekund.',
            ],
        ],
    ],
];
