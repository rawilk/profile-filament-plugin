<?php

declare(strict_types=1);

return [
    'label' => 'Configurar',
    'another-label' => 'Configurar outro',

    'modal' => [
        'heading' => 'Configurar aplicativo autenticador',

        'description' => <<<'BLADE'
        Você precisará de um aplicativo autenticador ou extensão de navegador como o <x-filament::link href="https://support.1password.com/one-time-passwords/" target="_blank">1Password</x-filament::link> ou Google Authenticator (<x-filament::link href="https://itunes.apple.com/us/app/google-authenticator/id388497605" target="_blank">iOS</x-filament::link>, <x-filament::link href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</x-filament::link>) para concluir este processo.
        BLADE,

        'content' => [
            'qr-code' => [
                'title' => 'Escanear código QR',
                'instruction' => 'Escaneie o código QR abaixo ou insira manualmente a chave secreta em seu aplicativo autenticador.',
                'alt' => 'Código QR para escanear com um aplicativo autenticador',
            ],

            'text-code' => [
                'title' => 'Não consegue escanear o código QR?',
                'instruction' => 'Insira este segredo em vez disso:',

                'actions' => [
                    'copy' => [
                        'label' => 'Copiar código',
                        'copied' => 'Copiado',
                    ],
                ],
            ],
        ],

        'form' => [
            'steps' => [
                'app' => [
                    'label' => 'Registro do Aplicativo',
                ],

                'name' => [
                    'label' => 'Nome',
                ],
            ],

            'code' => [
                'title' => 'Obter código de verificação',
                'instruction' => 'Digite o código de 6 dígitos que você vê em seu aplicativo autenticador.',

                'label' => 'Digite o código de verificação',

                'validation-attribute' => 'código',

                'below-content' => 'Você precisará digitar o código de 6 dígitos do seu aplicativo autenticador sempre que fizer login ou realizar ações confidenciais.',

                'messages' => [
                    'invalid' => 'O código que você digitou é inválido.',
                    'throttled' => 'Muitas tentativas. Por favor, tente novamente mais tarde.',
                ],
            ],

            'name' => [
                'label' => 'Nome do aplicativo',

                'help' => 'Você pode dar ao aplicativo um nome significativo para poder identificá-lo mais tarde.',

                'placeholder' => '1Password',

                'default-name' => 'Aplicativo autenticador',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Ativar aplicativo autenticador',
            ],
        ],
    ],

    'notifications' => [
        'enabled' => [
            'title' => 'O aplicativo autenticador foi ativado.',
        ],
    ],
];
