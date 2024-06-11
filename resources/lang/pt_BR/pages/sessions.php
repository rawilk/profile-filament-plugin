<?php

declare(strict_types=1);

return [
    'title' => 'Sessões',

    'manager' => [
        'heading' => 'Sessões web',
        'description' => 'Se necessário, você também pode desconectar-se de todas as suas outras sessões de navegador em todos os seus dispositivos. Se você sentir que sua conta foi comprometida, você também deve atualizar sua senha.',
        'list_description' => 'Esta é uma lista de dispositivos que fizeram login na sua conta. Revogue quaisquer sessões que você não reconhecer.',
        'unknown_platform' => 'Desconhecido',
        'unknown_browser' => 'Desconhecido',
        'ip_info_tooltip' => 'Pesquisar a geolocalização deste endereço IP.',
        'current_device' => 'Este dispositivo',
        'last_activity' => 'Última atividade :time',

        'password_input_label' => 'Sua senha',
        'password_input_helper' => 'Sua senha é necessária para forçar o logout de sessões que possam ter o cookie de lembrança configurado.',

        'actions' => [

            'revoke' => [
                'trigger' => 'Revogar sessão',
                'success' => 'Sessão foi revogada.',
                'submit_button' => 'Revogar sessão',
            ],

            'revoke_all' => [
                'trigger' => 'Revogar todas as outras sessões',
                'success' => 'Todas as outras sessões foram revogadas.',
                'submit_button' => 'Revogar todas as outras sessões',
                'modal_title' => 'Revogar todas as outras sessões',
            ],

        ],
    ],
];
