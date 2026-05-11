<?php

declare(strict_types=1);

return [
    'challenge' => [
        'heading' => 'Kod uwierzytelniający',

        'form' => [
            'code' => [
                'label' => 'Wprowadź 6-cyfrowy kod z aplikacji uwierzytelniającej',
                'validation-attribute' => 'kod',

                'messages' => [
                    'invalid' => 'Wprowadzony kod jest nieprawidłowy.',
                ],
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Zweryfikuj',
            ],

            'change-to' => [
                'label' => 'Użyj aplikacji uwierzytelniającej',
            ],
        ],
    ],
];
