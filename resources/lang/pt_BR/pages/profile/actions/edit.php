<?php

declare(strict_types=1);

return [
    'label' => 'Editar',

    'modal' => [
        'heading' => 'Edite suas informações',

        'actions' => [
            'submit' => [
                'label' => 'Salvar',
            ],
        ],

        'form' => [
            'name' => [
                'label' => 'Seu nome',
                'validation-attribute' => 'nome',
            ],
        ],
    ],

    'notifications' => [
        'saved' => [
            'title' => 'As informações do seu perfil foram atualizadas!',
        ],
    ],
];
