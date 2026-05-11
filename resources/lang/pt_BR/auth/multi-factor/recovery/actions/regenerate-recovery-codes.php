<?php

declare(strict_types=1);

return [
    'label' => 'Gerar novos códigos',

    'modal' => [
        'title' => 'Gerar novos códigos de recuperação',
        'description' => <<<'BLADE'
        Ao gerar novos códigos de recuperação, você deve baixar ou imprimir os novos códigos. **Seus códigos antigos não funcionarão mais.**
        BLADE,

        'actions' => [
            'confirm' => [
                'label' => 'Gerar novos códigos',
            ],
        ],
    ],

    'notifications' => [
        'regenerated' => [
            'title' => 'Novos códigos de recuperação foram gerados',
        ],
    ],
];
