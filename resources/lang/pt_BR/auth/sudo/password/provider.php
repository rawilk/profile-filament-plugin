<?php

declare(strict_types=1);

return [
    'challenge' => [
        'form' => [
            'password' => [
                'label' => 'Sua senha',
                'validation-attribute' => 'senha',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Confirmar',
            ],

            'change-to' => [
                'label' => 'Usar sua senha',
            ],
        ],
    ],
];
