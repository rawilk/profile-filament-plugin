<?php

declare(strict_types=1);

return [
    'label' => 'Configurar',
    'another-label' => 'Configurar outro',

    'modal' => [
        'heading' => 'Criar uma chave de acesso para sua conta',

        'description' => 'Crie e registre uma chave de acesso para aumentar a segurança da sua conta durante a autenticação.',

        'form' => [
            'name' => [
                'label' => 'Nome da chave',

                'validation-attribute' => 'nome',

                'placeholder' => '1Password',

                'default-name' => 'Chave de segurança',
            ],
        ],

        'actions' => [
            'register' => [
                'label' => 'Iniciar registro da chave',
            ],
        ],
    ],

    'messages' => [
        'failed' => 'O registro da chave de segurança falhou',

        'throttled' => [
            'title' => 'Muitas tentativas',
            'body' => 'Por favor, tente novamente em :seconds segundos.',
        ],
    ],

    'notifications' => [
        'enabled' => [
            'title' => 'A chave de segurança foi criada com sucesso.',
        ],
    ],
];
