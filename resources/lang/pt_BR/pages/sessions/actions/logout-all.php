<?php

declare(strict_types=1);

return [
    'label' => 'Desconectar de todos os outros dispositivos',

    'modal' => [
        'heading' => 'Desconectar de todos os outros dispositivos',

        'form' => [
            'password' => [
                'label' => 'Sua senha',
                'validation-attribute' => 'senha',
                'help' => 'Sua senha é necessária para forçar o logout de sessões que possam ter o cookie de lembrança configurado.',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Desconectar de todos os outros dispositivos',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Todos os outros dispositivos foram desconectados.',
        ],
    ],
];
