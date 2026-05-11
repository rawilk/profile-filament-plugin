<?php

declare(strict_types=1);

return [
    'modal' => [
        'heading' => 'Nowe kody odzyskiwania',
        'description' => <<<'BLADE'
        Kody odzyskiwania mogą być użyte do odzyskania dostępu do konta w przypadku utraty urządzenia do uwierzytelniania dwuskładnikowego. **Każdy kod może być użyty tylko raz.**
        <br><br>
        Kody te zostaną wyświetlone tylko raz, więc upewnij się, że przechowujesz je w bezpiecznym miejscu, na przykład w menedżerze haseł.
        BLADE,

        'actions' => [
            'copy' => [
                'label' => 'Kopiuj',
            ],

            'download' => [
                'label' => 'Pobierz',
            ],

            'submit' => [
                'label' => 'Gotowe',
            ],
        ],

        'form' => [
            'confirm' => [
                'label' => 'Zapisałem swoje kody odzyskiwania i przechowuję je bezpiecznie',

                'messages' => [
                    'accepted' => 'Musisz zapisać kody odzyskiwania przed kontynuowaniem.',
                ],
            ],
        ],
    ],

    'messages' => [
        'copied' => 'Skopiowano',
    ],
];
