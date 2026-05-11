<?php

declare(strict_types=1);

return [
    'label' => 'Zmień e-mail',

    'modal' => [
        'heading' => 'Edytuj adres e-mail',

        'form' => [
            'email' => [
                'label' => 'Nowy adres e-mail',
                'validation-attribute' => 'e-mail',
                'placeholder' => 'example@:host',
                'help' => 'Wyślemy e-mail na ten adres, aby zweryfikować, że masz do niego dostęp. Twoje zmiany nie wejdą w życie, dopóki nie zweryfikujesz nowego adresu e-mail.',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Zaktualizuj e-mail',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Sukces!',
            'body' => 'Twój adres e-mail został zaktualizowany.',
            'body-pending' => 'Sprawdź swój nowy adres e-mail, aby znaleźć link weryfikacyjny.',
        ],
    ],
];
