<?php

declare(strict_types=1);

return [
    'challenge' => [
        'heading' => 'Código de verificação por e-mail',

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
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Confirmar',
            ],

            'change-to' => [
                'label' => 'Usar um código de verificação por e-mail',
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
