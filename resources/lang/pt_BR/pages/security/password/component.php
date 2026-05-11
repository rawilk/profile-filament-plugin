<?php

declare(strict_types=1);

return [
    'heading' => 'Alterar senha',

    'form' => [
        'password' => [
            'label' => 'Nova senha',
            'validation-attribute' => 'senha',
        ],

        'password-confirmation' => [
            'label' => 'Confirmar nova senha',
            'validation-attribute' => 'confirmação de senha',
        ],

        'current-password' => [
            'label' => 'Senha atual',
            'validation-attribute' => 'senha atual',
            'below-content' => 'Por segurança, confirme sua senha para continuar.',
        ],
    ],

    'actions' => [
        'save' => [
            'label' => 'Atualizar senha',
        ],

        'forgot-password' => [
            'label' => 'Esqueci minha senha',
        ],
    ],

    'notifications' => [
        'saved' => [
            'title' => 'Senha atualizada!',
        ],

        'throttled' => [
            'title' => 'Muitas solicitações.',
            'body' => 'Por favor, tente novamente em :seconds segundos.',
        ],
    ],
];
