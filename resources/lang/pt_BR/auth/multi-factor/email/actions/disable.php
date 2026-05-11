<?php

declare(strict_types=1);

return [
    'label' => 'Desativar',

    'modal' => [
        'heading' => 'Desativar códigos de verificação por e-mail',

        'description' => 'Tem certeza de que deseja parar de receber códigos de verificação por e-mail? Desativar isso removerá uma camada extra de segurança da sua conta.',

        'actions' => [
            'submit' => [
                'label' => 'Desativar',
            ],
        ],
    ],

    'notifications' => [
        'disabled' => [
            'title' => 'Os códigos de verificação por e-mail foram desativados.',
        ],
    ],
];
