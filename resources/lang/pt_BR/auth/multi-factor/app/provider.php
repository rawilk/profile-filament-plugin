<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Aplicativo autenticador',
        'description' => 'Use um aplicativo de autenticação ou extensão de navegador para obter códigos de autenticação de dois fatores quando solicitado.',

        'select-label' => 'Aplicativo autenticador',

        'messages' => [
            'configured' => 'Configurado',
        ],

        'list' => [
            'toggle-list' => '1 aplicativo autenticador configurado|:count aplicativos autenticadores configurados',
        ],
    ],

    'challenge-form' => [
        'code' => [
            'label' => 'Digite o código de 6 dígitos do seu aplicativo autenticador',
            'validation-attribute' => 'código',

            'messages' => [
                'invalid' => 'O código que você digitou é inválido.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Verificar conta',
            ],

            'change-provider' => [
                'label' => 'Aplicativo autenticador',
            ],
        ],
    ],
];
