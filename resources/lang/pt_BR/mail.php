<?php

declare(strict_types=1);

return [
    'verify-email-change' => [
        'subject' => 'Verifique seu endereço de e-mail',
        'action' => 'Verificar Novo Endereço de E-mail',

        'lines' => [
            'Uma solicitação foi feita em sua conta para alterar seu endereço de e-mail para :email. Clique no botão abaixo para verificar seu novo endereço de e-mail.',
            'Atenção — este link funciona apenas por :expire. Depois disso, você precisará solicitar um novo para verificar seu endereço de e-mail.',
            'Se você não atualizou seu endereço de e-mail, nenhuma ação adicional é necessária.',
        ],
    ],

    'notice-of-email-change-request' => [
        'subject' => 'Seu endereço de e-mail está sendo alterado',
        'action' => 'Bloquear Alteração de E-mail',

        'lines' => [
            'Recebemos uma solicitação para alterar o endereço de e-mail associado à sua conta.',
            'Uma vez verificado, o novo endereço de e-mail em sua conta será: :email.',
            'Você pode bloquear a alteração antes que ela seja verificada clicando no botão abaixo.',
            'Se você não fez esta solicitação, entre em contato conosco imediatamente.',
        ],
    ],
];
