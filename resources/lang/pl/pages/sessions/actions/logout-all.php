<?php

declare(strict_types=1);

return [
    'label' => 'Wyloguj ze wszystkich innych urządzeń',

    'modal' => [
        'heading' => 'Wyloguj ze wszystkich innych urządzeń',

        'form' => [
            'password' => [
                'label' => 'Twoje hasło',
                'validation-attribute' => 'hasło',
                'help' => 'Twoje hasło jest wymagane, aby wymusić wylogowanie z sesji, które mogą mieć ustawione ciasteczko zapamiętywania.',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Wyloguj ze wszystkich innych urządzeń',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Wylogowano ze wszystkich innych urządzeń.',
        ],
    ],
];
