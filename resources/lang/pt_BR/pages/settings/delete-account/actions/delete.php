<?php

declare(strict_types=1);

return [
    'label' => 'Excluir sua conta',

    'modal' => [
        'heading' => 'Excluir sua conta',

        'form' => [
            'email' => [
                'label' => 'Para confirmar, digite seu e-mail, ":email", na caixa abaixo:',

                'validation-attribute' => 'e-mail',

                'messages' => [
                    'incorrect' => 'O endereço de e-mail que você digitou não está correto.',
                ],
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Excluir sua conta',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Sua conta foi excluída.',
        ],
    ],
];
