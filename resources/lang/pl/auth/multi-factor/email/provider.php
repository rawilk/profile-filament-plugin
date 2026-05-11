<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'E-mailowe kody weryfikacyjne',
        'description' => 'Otrzymuj tymczasowy kod na swój adres e-mail, aby zweryfikować swoją tożsamość podczas żądań uwierzytelnienia.',

        'select-label' => 'E-mailowe kody weryfikacyjne',

        'messages' => [
            'disabled' => 'Wyłączone',
            'enabled' => 'Włączone',
        ],
    ],

    'challenge-form' => [
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

            'problems' => [
                'title' => 'Nie otrzymałeś kodu?',
                'description' => 'Otrzymanie go może zająć kilka chwil. Sprawdź folder spam. W przeciwnym razie możesz poprosić o nowy kod.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Potwierdź',
            ],

            'change-provider' => [
                'label' => 'E-mailowy kod weryfikacyjny',
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
