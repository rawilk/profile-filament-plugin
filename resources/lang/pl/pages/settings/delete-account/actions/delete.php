<?php

declare(strict_types=1);

return [
    'label' => 'Usuń swoje konto',

    'modal' => [
        'heading' => 'Usuń swoje konto',

        'form' => [
            'email' => [
                'label' => 'Aby potwierdzić, wpisz swój adres e-mail, ":email", w polu poniżej:',

                'validation-attribute' => 'e-mail',

                'messages' => [
                    'incorrect' => 'Wprowadzony adres e-mail jest niepoprawny.',
                ],
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Usuń swoje konto',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Twoje konto zostało usunięte.',
        ],
    ],
];
