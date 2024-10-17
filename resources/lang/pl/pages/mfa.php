<?php

declare(strict_types=1);

return [
    // Fallback page title
    'heading' => 'Uwierzytelnianie dwuskładnikowe',

    // Fallback alternatives heading
    'alternative_heading' => 'Masz problemy?',

    'totp' => [
        'heading' => 'Uwierzytelnianie dwuskładnikowe',
        'label' => 'Kod uwierzytelniający',
        'placeholder' => '6-cyfrowy kod',
        'hint' => 'Otwórz swoją aplikację uwierzytelniającą (TOTP) lub rozszerzenie przeglądarki, aby zobaczyć kod uwierzytelniający.',
        'alternative_heading' => 'Nie możesz zweryfikować się za pomocą aplikacji uwierzytelniającej?',
        'use_label' => 'Użyj aplikacji uwierzytelniającej',
        'invalid' => 'Wprowadzony kod jest nieprawidłowy.',
    ],

    'recovery_code' => [
        'heading' => 'Odzyskiwanie dwuskładnikowe',
        'label' => 'Kod odzyskiwania',
        'placeholder' => 'XXXXX-XXXXX',
        'hint' => 'Jeśli nie masz dostępu do swojego urządzenia mobilnego, wprowadź jeden z kodów odzyskiwania, aby zweryfikować swoją tożsamość.',
        'alternative_heading' => 'Nie masz kodu odzyskiwania?',
        'use_label' => 'Użyj kodu odzyskiwania',
        'invalid' => 'Wprowadzony kod jest nieprawidłowy.',
    ],

    'webauthn' => [
        'heading' => 'Uwierzytelnianie dwuskładnikowe',
        'label' => 'Klucz bezpieczeństwa',
        'label_including_passkeys' => 'Passkey lub klucz bezpieczeństwa',
        'hint' => 'Gdy będziesz gotowy, uwierzytelnij się za pomocą przycisku poniżej.',
        'alternative_heading' => 'Nie możesz zweryfikować się za pomocą klucza bezpieczeństwa?',
        'use_label' => 'Użyj klucza bezpieczeństwa',
        'use_label_including_passkeys' => 'Użyj passkey lub klucza bezpieczeństwa',
        'waiting' => 'Oczekiwanie na dane wejściowe z interakcji przeglądarki...',
        'failed' => 'Uwierzytelnienie nie powiodło się.',
        'retry' => 'Ponów próbę z kluczem bezpieczeństwa',
        'retry_including_passkeys' => 'Ponów próbę z passkey lub kluczem bezpieczeństwa',
        'passkey_login_button' => 'Zaloguj się za pomocą passkey',

        'assert' => [
            'failure_title' => 'Błąd',
            'failure' => 'Nie udało się zweryfikować Twojej tożsamości tym kluczem. Spróbuj użyć innego klucza lub innej formy uwierzytelnienia, aby zweryfikować swoją tożsamość.',
            'passkey_required' => 'Ten klucz nie może być użyty do uwierzytelnienia passkey.',
        ],

        'unsupported' => [
            'title' => 'Twoja przeglądarka nie jest wspierana!',
            'message' => 'Wygląda na to, że Twoja przeglądarka lub urządzenie nie jest kompatybilne z kluczami bezpieczeństwa WebAuthn. Możesz użyć jednej z innych metod dwuskładnikowych lub spróbować przełączyć się na wspieraną przeglądarkę.',
            'learn_more_link' => 'Dowiedz się więcej',
        ],
    ],

    'actions' => [
        'authenticate' => 'Zweryfikuj',
        'webauthn' => 'Użyj klucza bezpieczeństwa',
        'webauthn_including_passkeys' => 'Użyj passkey lub klucza bezpieczeństwa',
    ],

];
