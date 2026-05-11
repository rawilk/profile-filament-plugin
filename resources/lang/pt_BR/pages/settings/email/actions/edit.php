<?php

declare(strict_types=1);

return [
    'label' => 'Alterar e-mail',

    'modal' => [
        'heading' => 'Editar endereço de e-mail',

        'form' => [
            'email' => [
                'label' => 'Novo endereço de e-mail',
                'validation-attribute' => 'e-mail',
                'placeholder' => 'exemplo@:host',
                'help' => 'Enviaremos um e-mail para este endereço para verificar se você tem acesso a ele. Suas alterações não terão efeito até que você verifique o novo endereço de e-mail.',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Atualizar e-mail',
            ],
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Sucesso!',
            'body' => 'Seu endereço de e-mail foi atualizado.',
            'body-pending' => 'Verifique seu novo endereço de e-mail para um link de verificação.',
        ],
    ],
];
