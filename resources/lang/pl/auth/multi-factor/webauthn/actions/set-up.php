<?php

declare(strict_types=1);

return [
    'label' => 'Skonfiguruj',
    'another-label' => 'Skonfiguruj kolejny',

    'modal' => [
        'heading' => 'Utwórz klucz dostępu dla swojego konta',

        'description' => 'Utwórz i zarejestruj klucz dostępu, aby zwiększyć bezpieczeństwo swojego konta podczas uwierzytelniania.',

        'form' => [
            'name' => [
                'label' => 'Nazwa klucza',

                'validation-attribute' => 'nazwa',

                'placeholder' => '1Password',

                'default-name' => 'Klucz bezpieczeństwa',
            ],
        ],

        'actions' => [
            'register' => [
                'label' => 'Rozpocznij rejestrację klucza',
            ],
        ],
    ],

    'messages' => [
        'failed' => 'Rejestracja klucza bezpieczeństwa nie powiodła się',

        'throttled' => [
            'title' => 'Zbyt wiele prób',
            'body' => 'Spróbuj ponownie za :seconds sekund.',
        ],
    ],

    'notifications' => [
        'enabled' => [
            'title' => 'Klucz bezpieczeństwa został pomyślnie utworzony.',
        ],
    ],
];
