<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Aplikacja uwierzytelniająca',
        'description' => 'Użyj aplikacji uwierzytelniającej lub rozszerzenia przeglądarki, aby otrzymywać kody dwuskładnikowe, gdy zostaniesz o to poproszony.',

        'select-label' => 'Aplikacja uwierzytelniająca',

        'messages' => [
            'configured' => 'Skonfigurowano',
        ],

        'list' => [
            'toggle-list' => '1 skonfigurowana aplikacja uwierzytelniająca|:count skonfigurowanych aplikacji uwierzytelniających',
        ],
    ],

    'challenge-form' => [
        'code' => [
            'label' => 'Wprowadź 6-cyfrowy kod z aplikacji uwierzytelniającej',
            'validation-attribute' => 'kod',

            'messages' => [
                'invalid' => 'Wprowadzony kod jest nieprawidłowy.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Zweryfikuj konto',
            ],

            'change-provider' => [
                'label' => 'Aplikacja uwierzytelniająca',
            ],
        ],
    ],
];
