<?php

declare(strict_types=1);

return [
    'challenge' => [
        'heading' => 'Código de autenticação',

        'form' => [
            'code' => [
                'label' => 'Digite o código de 6 dígitos do seu aplicativo autenticador',
                'validation-attribute' => 'código',

                'messages' => [
                    'invalid' => 'O código que você digitou é inválido.',
                ],
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Verificar',
            ],

            'change-to' => [
                'label' => 'Usar seu aplicativo autenticador',
            ],
        ],
    ],
];
