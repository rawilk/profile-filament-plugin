<?php

declare(strict_types=1);

return [
    'alert' => [
        'dismiss' => 'Zamknij',
    ],

    'mfa_challenge' => [
        'invalid_challenged_user' => 'Nie można zweryfikować Twojego konta użytkownika.',
    ],

    'sudo_challenge' => [

        'title' => 'Potwierdź dostęp',
        'tip' => '**Wskazówka:** Wchodzisz w tryb sudo. Po wykonaniu chronionej akcji sudo, zostaniesz poproszony o ponowne uwierzytelnienie dopiero po kilku godzinach nieaktywności.',
        'cancel_button' => 'Anuluj',
        'signed_in_as' => 'Zalogowany jako: **:handle**',
        'expired' => 'Twoja sesja sudo wygasła. Odśwież stronę, aby spróbować ponownie.',

        'alternative_heading' => 'Masz problemy?',

        'totp' => [
            'use_label' => 'Użyj aplikacji uwierzytelniającej',
            'heading' => 'Kod uwierzytelniający',
            'help_text' => 'Otwórz swoją aplikację lub rozszerzenie przeglądarki do uwierzytelniania dwuskładnikowego (TOTP), aby zobaczyć swój kod uwierzytelniający.',
            'placeholder' => '6-cyfrowy kod',
            'invalid' => 'Wprowadzony kod jest nieprawidłowy.',
            'submit' => 'Zweryfikuj',
        ],

        'webauthn' => [
            'use_label' => 'Użyj klucza bezpieczeństwa',
            'use_label_including_passkeys' => 'Użyj passkey lub klucza bezpieczeństwa',
            'heading' => 'Klucz bezpieczeństwa',
            'heading_including_passkeys' => 'Passkey lub klucz bezpieczeństwa',
            'waiting' => 'Oczekiwanie na dane wejściowe z interakcji przeglądarki...',
            'failed' => 'Uwierzytelnienie nie powiodło się.',
            'retry' => 'Ponów próbę z kluczem bezpieczeństwa',
            'retry_including_passkeys' => 'Ponów próbę z passkey lub kluczem bezpieczeństwa',
            'submit' => 'Użyj klucza bezpieczeństwa',
            'submit_including_passkeys' => 'Użyj passkey lub klucza bezpieczeństwa',
            'hint' => 'Gdy będziesz gotowy, uwierzytelnij się za pomocą przycisku poniżej.',
            'invalid' => 'Uwierzytelnienie nie powiodło się.',
        ],

        'password' => [
            'use_label' => 'Użyj swojego hasła',
            'input_label' => 'Twoje hasło',
            'submit' => 'Potwierdź',
            'invalid' => 'Nieprawidłowe hasło.',
        ],

    ],

    'masked_value' => [
        'reveal_button' => 'Odkryj',
    ],

];
