<?php

declare(strict_types=1);

return [
    'heading' => 'Autenticação de dois fatores',

    'description' => 'A autenticação de dois fatores adiciona uma camada adicional de segurança à sua conta, exigindo mais do que apenas uma senha para entrar. Configure qualquer um dos provedores de dois fatores abaixo para ativar a autenticação de dois fatores em sua conta.',

    'messages' => [
        'enabled' => 'Ativada',
        'disabled' => 'Inativa',
    ],

    'preferred-mfa-provider' => [
        'label' => 'Provedor de dois fatores preferencial',
        'description' => 'Defina seu provedor de dois fatores preferencial para usar ao se autenticar.',
        'placeholder' => 'Sem preferência',

        'notifications' => [
            'saved' => [
                'title' => 'Preferência de provedor de dois fatores salva.',
            ],
        ],
    ],
];
