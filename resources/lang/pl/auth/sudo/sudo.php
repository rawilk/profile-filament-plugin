<?php

declare(strict_types=1);

return [
    'challenge' => [
        'title' => 'Potwierdź dostęp',

        'heading' => 'Potwierdź dostęp',

        'alternate-options' => 'Masz problemy?',

        'signed-in-as' => [
            'content' => 'Zalogowany jako: **:handle**',
        ],

        'tip' => <<<'BLADE'
        **Wskazówka:** Wchodzisz w tryb sudo. Po wykonaniu czynności chronionej przez sudo, zostaniesz poproszony o ponowne uwierzytelnienie dopiero po kilku godzinach nieaktywności.
        BLADE,
    ],

    'messages' => [
        'expired' => 'Twoja sesja sudo wygasła.',
    ],

    'notifications' => [
        'throttled' => [
            'title' => 'Zbyt wiele prób',
            'body' => 'Spróbuj ponownie za :seconds sekund.',
        ],
    ],
];
