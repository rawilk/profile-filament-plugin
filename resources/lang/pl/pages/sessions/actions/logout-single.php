<?php

declare(strict_types=1);

return [
    'label' => 'Wyloguj urządzenie',

    'modal' => [
        'heading' => 'Wyloguj urządzenie',

        'form' => [
            'password' => [
                'label' => 'Twoje hasło',
                'validation-attribute' => 'hasło',
                'help' => 'Twoje hasło jest wymagane, aby wymusić wylogowanie z sesji, które mogą mieć ustawione ciasteczko zapamiętywania.',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Wyloguj urządzenie',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Urządzenie zostało wylogowane.',
        ],
    ],
];
