<?php

declare(strict_types=1);

return [
    'challenge' => [
        'form' => [
            'password' => [
                'label' => 'Twoje hasło',
                'validation-attribute' => 'hasło',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Potwierdź',
            ],

            'change-to' => [
                'label' => 'Użyj swojego hasła',
            ],
        ],
    ],
];
