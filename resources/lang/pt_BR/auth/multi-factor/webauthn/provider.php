<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Chaves de acesso e chaves de segurança',

        'description' => 'Com chaves de acesso, você pode fazer login em sua conta de forma segura usando apenas sua impressão digital, rosto, bloqueio de tela ou uma chave segura armazenada em um gerenciador de senhas. As chaves de acesso também podem ser usadas como uma segunda etapa ao fazer login com sua senha.',

        'select-label' => 'Chaves de acesso ou chaves de segurança',

        'messages' => [
            'configured' => 'Configurado',
            'not-passkey' => 'Esta chave só pode ser usada com uma senha.',
        ],

        'list' => [
            'toggle-list' => '1 chave de segurança configurada|:count chaves de segurança configuradas',
        ],
    ],

    'messages' => [
        'unsupported' => [
            'title' => 'Seu navegador não é suportado!',
            'body' => 'Parece que seu navegador ou dispositivo não é compatível com chaves de segurança WebAuthn. Você pode usar um de seus outros métodos de múltiplos fatores ou tentar mudar para um navegador suportado.',
            'learn-more-link' => 'Saiba mais',
        ],

        'waiting-for-input' => 'Aguardando entrada da interação do navegador...',
    ],

    'challenge-form' => [
        'form' => [
            'prompt' => [
                'label' => 'Verifique sua identidade com sua chave de acesso ou chave de segurança.',
            ],
        ],

        'messages' => [
            'failed' => 'A autenticação por chave de acesso falhou ou expirou. Por favor, tente novamente.',
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Usar chave de acesso ou chave de segurança',
            ],

            'change-provider' => [
                'label' => 'Chave de acesso ou chave de segurança',
            ],
        ],
    ],
];
