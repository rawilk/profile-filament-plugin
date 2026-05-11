<?php

declare(strict_types=1);

return [
    'title' => 'Autenticação por chave de segurança e chave de acesso',

    'form' => [
        'messages' => [
            'prompt' => 'Quando estiver pronto, clique no botão Autenticar para iniciar o processo de autenticação.',

            'popups-disabled' => [
                'passkey' => 'Por favor, permita popups para este site para usar sua chave de acesso.',
                'webauthn' => 'Por favor, permita popups para este site para usar sua chave de segurança ou chave de acesso.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Autenticar',
            ],
        ],
    ],
];
