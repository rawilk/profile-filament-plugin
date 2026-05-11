<?php

declare(strict_types=1);

return [
    'challenge' => [
        'heading' => 'Klucz dostępu lub klucz bezpieczeństwa',

        'form' => [
            'prompt' => [
                'label' => 'Gdy będziesz gotowy, uwierzytelnij się za pomocą poniższego przycisku.',
            ],
        ],

        'messages' => [
            'failed' => 'Uwierzytelnianie kluczem dostępu nie powiodło się lub przekroczono limit czasu. Spróbuj ponownie.',
        ],

        'actions' => [
            'generate-options' => [
                'label' => 'Użyj klucza dostępu lub klucza bezpieczeństwa',
            ],

            'change-to' => [
                'label' => 'Użyj swojego klucza dostępu lub klucza bezpieczeństwa',
            ],
        ],
    ],
];
