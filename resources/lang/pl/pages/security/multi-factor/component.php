<?php

declare(strict_types=1);

return [
    'heading' => 'Uwierzytelnianie dwuskładnikowe',

    'description' => 'Uwierzytelnianie dwuskładnikowe dodaje dodatkową warstwę bezpieczeństwa do Twojego konta, wymagając więcej niż tylko hasła do zalogowania. Skonfiguruj dowolną z poniższych metod, aby włączyć uwierzytelnianie dwuskładnikowe na swoim koncie.',

    'messages' => [
        'enabled' => 'Włączone',
        'disabled' => 'Nieaktywne',
    ],

    'preferred-mfa-provider' => [
        'label' => 'Preferowana metoda uwierzytelniania dwuskładnikowego',
        'description' => 'Ustaw preferowaną metodę uwierzytelniania dwuskładnikowego, której chcesz używać podczas logowania.',
        'placeholder' => 'Brak preferencji',

        'notifications' => [
            'saved' => [
                'title' => 'Preferencja metody uwierzytelniania dwuskładnikowego została zapisana.',
            ],
        ],
    ],
];
