<?php

declare(strict_types=1);

return [
    'title' => 'Verificação em 2 etapas',

    'heading' => 'Verifique sua identidade',

    'subheading' => 'Para manter sua conta segura, queremos ter certeza de que é realmente você tentando fazer login.',

    'form' => [
        'provider' => [
            'heading' => 'Escolha como deseja verificar sua identidade:',
        ],
    ],

    'actions' => [
        'change-provider' => [
            'label' => 'Tentar de outra forma',
        ],
    ],

    'messages' => [
        'password-confirmation-expired' => 'Por favor, confirme sua senha novamente para retomar a autenticação de múltiplos fatores.',
    ],
];
