<?php

declare(strict_types=1);

return [
    'title' => 'Uwierzytelnianie kluczem bezpieczeństwa i kluczem dostępu',

    'form' => [
        'messages' => [
            'prompt' => 'Gdy będziesz gotowy, kliknij przycisk Uwierzytelnij, aby rozpocząć proces uwierzytelniania.',

            'popups-disabled' => [
                'passkey' => 'Zezwól na wyskakujące okienka dla tej witryny, aby użyć klucza dostępu.',
                'webauthn' => 'Zezwól na wyskakujące okienka dla tej witryny, aby użyć klucza bezpieczeństwa lub klucza dostępu.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Uwierzytelnij',
            ],
        ],
    ],
];
