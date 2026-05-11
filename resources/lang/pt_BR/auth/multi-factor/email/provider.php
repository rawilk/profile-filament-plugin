<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Códigos de verificação por e-mail',
        'description' => 'Receba um código temporário em seu endereço de e-mail para verificar sua identidade durante as solicitações de autenticação.',

        'select-label' => 'Códigos de verificação por e-mail',

        'messages' => [
            'disabled' => 'Desativado',
            'enabled' => 'Ativado',
        ],
    ],

    'challenge-form' => [
        'form' => [
            'details' => [
                'label' => 'Enviamos seu código de verificação para <strong>:email</strong>.',
            ],

            'code' => [
                'label' => 'Digite seu código de 6 dígitos',
                'help' => 'Seu código expira em :minutes minutos.',
                'placeholder' => 'XXXXXX',
                'validation-attribute' => 'código',

                'messages' => [
                    'invalid' => 'O código que você digitou é inválido.',
                ],
            ],

            'problems' => [
                'title' => 'Não recebeu um código?',
                'description' => 'Pode levar alguns instantes para você recebê-lo. Verifique sua pasta de spam. Caso contrário, você pode solicitar um novo código.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Confirmar',
            ],

            'change-provider' => [
                'label' => 'Código de Verificação por E-mail',
            ],

            'resend-code' => [
                'label' => 'Solicitar um novo código',

                'notifications' => [
                    'resent' => [
                        'title' => 'Enviamos um novo código por e-mail.',
                    ],
                ],
            ],
        ],

        'notifications' => [
            'throttled' => [
                'title' => 'Muitas solicitações',
                'body' => 'Por favor, tente novamente em :seconds segundos.',
            ],
        ],
    ],
];
