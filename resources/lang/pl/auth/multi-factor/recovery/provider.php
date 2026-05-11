<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Kody odzyskiwania',
        'description' => 'Kody odzyskiwania mogą być użyte do uzyskania dostępu do konta w przypadku utraty dostępu do urządzenia i niemożności otrzymania kodów uwierzytelniania dwuskładnikowego.',

        'messages' => [
            'codes-remaining' => 'Pozostał 1 kod|Pozostało :count kodów',

            'needs-mfa-enabled' => 'Aby wygenerować kody odzyskiwania, należy najpierw skonfigurować co najmniej jednego dostawcę uwierzytelniania wieloskładnikowego',
        ],
    ],

    'challenge-form' => [
        'code' => [
            'label' => 'Wprowadź jeden ze swoich kodów odzyskiwania',
            'validation-attribute' => 'kod odzyskiwania',

            'messages' => [
                'invalid' => 'Wprowadzony kod odzyskiwania jest nieprawidłowy.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Zweryfikuj konto',
            ],

            'change-provider' => [
                'label' => 'Kod odzyskiwania',
            ],
        ],
    ],
];
