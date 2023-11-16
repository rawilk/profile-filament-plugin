<?php

declare(strict_types=1);

return [
    'title' => 'Profile',
    'heading' => 'Your profile',
    'user_menu_label' => 'Your settings',

    'info' => [
        'heading' => 'Profile information',

        'name' => [
            'label' => 'Name',
            'form_label' => 'Your name',
        ],

        'created_at' => [
            'label' => 'User since',
        ],

        'actions' => [

            'edit' => [
                'trigger' => 'Edit',
                'modal_title' => 'Edit your information',
                'submit' => 'Save',
                'success' => 'Your profile has been updated.',
            ],

        ],
    ],
];
