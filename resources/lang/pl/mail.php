<?php

declare(strict_types=1);

return [
    'verify-email-change' => [
        'subject' => 'Zweryfikuj swój adres e-mail',
        'action' => 'Zweryfikuj nowy adres e-mail',

        'lines' => [
            'Złożono prośbę o zmianę adresu e-mail na Twoim koncie na :email. Kliknij poniższy przycisk, aby zweryfikować swój nowy adres e-mail.',
            'Uwaga — ten link działa tylko przez :expire. Po tym czasie musisz poprosić o nowy, aby zweryfikować swój adres e-mail.',
            'Jeśli nie aktualizowałeś swojego adresu e-mail, nie musisz podejmować żadnych działań.',
        ],
    ],

    'notice-of-email-change-request' => [
        'subject' => 'Twój adres e-mail jest zmieniany',
        'action' => 'Zablokuj zmianę adresu e-mail',

        'lines' => [
            'Otrzymaliśmy prośbę o zmianę adresu e-mail powiązanego z Twoim kontem.',
            'Po zweryfikowaniu, nowym adresem e-mail na Twoim koncie będzie: :email.',
            'Możesz zablokować zmianę przed jej zweryfikowaniem, klikając poniższy przycisk.',
            'Jeśli to nie Ty złożyłeś tę prośbę, skontaktuj się z nami natychmiast.',
        ],
    ],
];
