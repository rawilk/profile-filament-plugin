<?php

declare(strict_types=1);

return [
    'label' => 'Edytuj',

    'modal' => [
        'heading' => 'Edytuj swoje informacje',

        'actions' => [
            'submit' => [
                'label' => 'Zapisz',
            ],
        ],

        'form' => [
            'name' => [
                'label' => 'Twoje imię i nazwisko',
                'validation-attribute' => 'imię i nazwisko',
            ],
        ],
    ],

    'notifications' => [
        'saved' => [
            'title' => 'Twój profil został zaktualizowany!',
        ],
    ],
];
