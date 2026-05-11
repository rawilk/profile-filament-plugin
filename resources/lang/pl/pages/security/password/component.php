<?php

declare(strict_types=1);

return [
    'heading' => 'Zmień hasło',

    'form' => [
        'password' => [
            'label' => 'Nowe hasło',
            'validation-attribute' => 'nowe hasło',
        ],

        'password-confirmation' => [
            'label' => 'Potwierdź nowe hasło',
            'validation-attribute' => 'potwierdzenie nowego hasła',
        ],

        'current-password' => [
            'label' => 'Obecne hasło',
            'validation-attribute' => 'obecne hasło',
            'below-content' => 'Ze względów bezpieczeństwa prosimy o potwierdzenie hasła, aby kontynuować.',
        ],
    ],

    'actions' => [
        'save' => [
            'label' => 'Zaktualizuj hasło',
        ],

        'forgot-password' => [
            'label' => 'Zapomniałem hasła',
        ],
    ],

    'notifications' => [
        'saved' => [
            'title' => 'Hasło zaktualizowane!',
        ],

        'throttled' => [
            'title' => 'Zbyt wiele żądań.',
            'body' => 'Spróbuj ponownie za :seconds sekund.',
        ],
    ],
];
