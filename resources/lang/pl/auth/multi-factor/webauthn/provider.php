<?php

declare(strict_types=1);

return [
    'management-schema' => [
        'label' => 'Klucze dostępu i klucze bezpieczeństwa',

        'description' => 'Dzięki kluczom dostępu możesz bezpiecznie logować się na swoje konto za pomocą odcisku palca, twarzy, blokady ekranu lub bezpiecznego klucza przechowywanego w menedżerze haseł. Klucze dostępu mogą być również używane jako drugi krok podczas logowania za pomocą hasła.',

        'select-label' => 'Klucze dostępu lub klucze bezpieczeństwa',

        'messages' => [
            'configured' => 'Skonfigurowano',
            'not-passkey' => 'Ten klucz może być używany tylko z hasłem.',
        ],

        'list' => [
            'toggle-list' => '1 skonfigurowany klucz bezpieczeństwa|:count skonfigurowanych kluczy bezpieczeństwa',
        ],
    ],

    'messages' => [
        'unsupported' => [
            'title' => 'Twoja przeglądarka nie jest obsługiwana!',
            'body' => 'Wygląda na to, że Twoja przeglądarka lub urządzenie nie jest kompatybilne z kluczami bezpieczeństwa WebAuthn. Możesz użyć jednej z innych metod wieloskładnikowych lub spróbować przełączyć się na obsługiwaną przeglądarkę.',
            'learn-more-link' => 'Dowiedz się więcej',
        ],

        'waiting-for-input' => 'Oczekiwanie na interakcję z przeglądarką...',
    ],

    'challenge-form' => [
        'form' => [
            'prompt' => [
                'label' => 'Zweryfikuj swoją tożsamość za pomocą klucza dostępu lub klucza bezpieczeństwa.',
            ],
        ],

        'messages' => [
            'failed' => 'Uwierzytelnianie kluczem dostępu nie powiodło się lub przekroczono limit czasu. Spróbuj ponownie.',
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Użyj klucza dostępu lub klucza bezpieczeństwa',
            ],

            'change-provider' => [
                'label' => 'Klucz dostępu lub klucz bezpieczeństwa',
            ],
        ],
    ],
];
