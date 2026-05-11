<?php

declare(strict_types=1);

return [
    'label' => 'Skonfiguruj',

    'modal' => [
        'heading' => 'Skonfiguruj e-mailowe kody weryfikacyjne',

        'description' => 'Będziesz musiał wprowadzić 6-cyfrowy kod weryfikacyjny, który wyślemy Ci e-mailem przy każdym logowaniu lub wykonywaniu wrażliwych czynności. Sprawdź swoją pocztę e-mail, aby znaleźć 6-cyfrowy kod i dokończyć konfigurację.',

        'form' => [
            'code' => [
                'label' => 'Wprowadź 6-cyfrowy kod wysłany e-mailem',
                'validation-attribute' => 'kod',

                'actions' => [
                    'resend' => [
                        'label' => 'Wyślij nowy kod e-mailem',

                        'notifications' => [
                            'resent' => [
                                'title' => 'Wysłaliśmy Ci nowy kod e-mailem.',
                            ],

                            'throttled' => [
                                'title' => 'Zbyt wiele prób ponownego wysłania.',
                                'body' => 'Odczekaj :seconds sekund przed poproszeniem o kolejny kod.',
                            ],
                        ],
                    ],
                ],

                'messages' => [
                    'invalid' => 'Wprowadzony kod jest nieprawidłowy.',
                    'rate-limited' => 'Zbyt wiele prób. Spróbuj ponownie później.',
                ],
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Włącz e-mailowe kody weryfikacyjne',
            ],
        ],
    ],

    'notifications' => [
        'enabled' => [
            'title' => 'E-mailowe kody weryfikacyjne zostały włączone.',
        ],
    ],
];
