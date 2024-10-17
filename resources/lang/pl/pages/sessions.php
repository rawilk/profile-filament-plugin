<?php

declare(strict_types=1);

return [
    'title' => 'Sesje',

    'manager' => [
        'heading' => 'Sesje internetowe',
        'description' => 'W razie potrzeby możesz również wylogować się ze wszystkich innych sesji przeglądarki na wszystkich swoich urządzeniach. Jeśli uważasz, że Twoje konto zostało naruszone, powinieneś również zaktualizować swoje hasło.',
        'list_description' => 'To jest lista urządzeń, które zalogowały się na Twoje konto. Zakończ wszelkie sesje, których nie rozpoznajesz.',
        'unknown_platform' => 'Nieznana',
        'unknown_browser' => 'Nieznana',
        'ip_info_tooltip' => 'Sprawdź geolokalizację tego adresu IP.',
        'current_device' => 'To urządzenie',
        'last_activity' => 'Ostatnia aktywność :time',

        'password_input_label' => 'Twoje hasło',
        'password_input_helper' => 'Twoje hasło jest wymagane do wymuszenia wylogowania z sesji, które mogą mieć ustawione ciasteczko "zapamiętaj mnie".',

        'actions' => [

            'revoke' => [
                'trigger' => 'Zakończ sesję',
                'success' => 'Sesja została zakończona.',
                'submit_button' => 'Zakończ sesję',
            ],

            'revoke_all' => [
                'trigger' => 'Zakończ wszystkie inne sesje',
                'success' => 'Wszystkie inne sesje zostały zakończone.',
                'submit_button' => 'Zakończ wszystkie inne sesje',
                'modal_title' => 'Zakończ wszystkie inne sesje',
            ],

        ],
    ],
];
