<?php

declare(strict_types=1);

return [
    'title' => 'Sesje',

    'manager' => [
        'heading' => 'Sesje internetowe',

        'description' => 'W razie potrzeby możesz również wylogować się ze wszystkich innych sesji przeglądarki na wszystkich swoich urządzeniach. Jeśli uważasz, że Twoje konto zostało naruszone, powinieneś również zaktualizować swoje hasło.',

        'list' => [
            'description' => 'To jest lista urządzeń, które zalogowały się na Twoje konto. Wyloguj wszelkie urządzenia, których nie rozpoznajesz.',

            'unknown' => [
                'platform' => 'Nieznana',
                'browser' => 'Nieznana',
            ],

            'ip-info' => [
                'tooltip' => 'Sprawdź geolokalizację tego adresu IP.',
            ],

            'current-device' => 'To urządzenie',

            'last-activity' => 'Ostatnia aktywność :time',
        ],
    ],
];
