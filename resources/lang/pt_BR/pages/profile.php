<?php

declare(strict_types = 1);

return [
    'title'           => 'Perfil',
    'heading'         => 'Seu perfil',
    'user_menu_label' => 'Seu Perfil',

    'info' => [
        'heading' => 'Informações do perfil',

        'name' => [
            'label'      => 'Nome',
            'form_label' => 'Seu nome',
        ],

        'created_at' => [
            'label' => 'Usuário desde',
        ],

        'actions' => [

            'edit' => [
                'trigger'     => 'Editar',
                'modal_title' => 'Edite suas informações',
                'submit'      => 'Salvar',
                'success'     => 'Seu perfil foi atualizado.',
            ],

        ],
    ],
];
