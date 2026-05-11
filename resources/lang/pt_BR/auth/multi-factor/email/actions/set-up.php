<?php

declare(strict_types=1);

return [
    'label' => 'Configurar',

    'modal' => [
        'heading' => 'Configurar códigos de verificação por e-mail',

        'description' => 'Você precisará digitar um código de verificação de 6 dígitos que enviaremos por e-mail sempre que fizer login ou realizar ações confidenciais. Verifique seu e-mail para um código de 6 dígitos para concluir a configuração.',

        'form' => [
            'code' => [
                'label' => 'Digite o código de 6 dígitos que enviamos por e-mail',
                'validation-attribute' => 'código',

                'actions' => [
                    'resend' => [
                        'label' => 'Enviar um novo código por e-mail',

                        'notifications' => [
                            'resent' => [
                                'title' => 'Enviamos um novo código por e-mail.',
                            ],

                            'throttled' => [
                                'title' => 'Muitas tentativas de reenvio.',
                                'body' => 'Aguarde :seconds segundos antes de solicitar outro código.',
                            ],
                        ],
                    ],
                ],

                'messages' => [
                    'invalid' => 'O código que você digitou é inválido.',
                    'rate-limited' => 'Muitas tentativas. Por favor, tente novamente mais tarde.',
                ],
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Ativar códigos de verificação por e-mail',
            ],
        ],
    ],

    'notifications' => [
        'enabled' => [
            'title' => 'Os códigos de verificação por e-mail foram ativados.',
        ],
    ],
];
