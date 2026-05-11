<?php

declare(strict_types=1);

return [
    'title' => 'Sessões',

    'manager' => [
        'heading' => 'Sessões web',

        'description' => 'Se necessário, você também pode desconectar-se de todas as suas outras sessões de navegador em todos os seus dispositivos. Se você sentir que sua conta foi comprometida, você também deve atualizar sua senha.',

        'list' => [
            'description' => 'Esta é uma lista de dispositivos que fizeram login na sua conta. Desconecte quaisquer dispositivos que você não reconhecer.',

            'unknown' => [
                'platform' => 'Desconhecido',
                'browser' => 'Desconhecido',
            ],

            'ip-info' => [
                'tooltip' => 'Pesquisar a geolocalização deste endereço IP.',
            ],

            'current-device' => 'Este dispositivo',

            'last-activity' => 'Última atividade :time',
        ],
    ],
];
