<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Códigos de recuperação',
        'description' => 'Os códigos de recuperação podem ser usados para acessar sua conta caso você perca o acesso ao seu dispositivo e não consiga receber códigos de autenticação de dois fatores.',

        'messages' => [
            'codes-remaining' => '1 código restante|:count códigos restantes',

            'needs-mfa-enabled' => 'Pelo menos um provedor de múltiplos fatores deve ser configurado primeiro para gerar códigos de recuperação',
        ],
    ],

    'challenge-form' => [
        'code' => [
            'label' => 'Digite um de seus códigos de recuperação',
            'validation-attribute' => 'código de recuperação',

            'messages' => [
                'invalid' => 'O código de recuperação que você digitou é inválido.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Verificar conta',
            ],

            'change-provider' => [
                'label' => 'Código de recuperação',
            ],
        ],
    ],
];
