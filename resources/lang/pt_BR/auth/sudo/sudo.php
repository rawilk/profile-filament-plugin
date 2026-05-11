<?php

declare(strict_types=1);

return [
    'challenge' => [
        'title' => 'Confirmar acesso',

        'heading' => 'Confirmar acesso',

        'alternate-options' => 'Está tendo problemas?',

        'signed-in-as' => [
            'content' => 'Sessão iniciada como: **:handle**',
        ],

        'tip' => <<<'BLADE'
        **Dica:** Você está entrando no modo sudo. Depois de realizar uma ação protegida por sudo, você só será solicitado a se reautenticar novamente após algumas horas de inatividade.
        BLADE,
    ],

    'messages' => [
        'expired' => 'Sua sessão sudo expirou.',
    ],

    'notifications' => [
        'throttled' => [
            'title' => 'Muitas tentativas',
            'body' => 'Por favor, tente novamente em :seconds segundos.',
        ],
    ],
];
