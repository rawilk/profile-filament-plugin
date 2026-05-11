<?php

declare(strict_types=1);

return [
    'label' => 'Desconectar dispositivo',

    'modal' => [
        'heading' => 'Desconectar dispositivo',

        'form' => [
            'password' => [
                'label' => 'Sua senha',
                'validation-attribute' => 'senha',
                'help' => 'Sua senha é necessária para forçar o logout de sessões que possam ter o cookie de lembrança configurado.',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Desconectar dispositivo',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'O dispositivo foi desconectado.',
        ],
    ],
];
