<?php

declare(strict_types=1);

return [
    'title' => 'Sessions',

    'manager' => [
        'heading' => 'Web sessions',
        'description' => 'If necessary, you may also log out of all of your other browser sessions across all of your devices. If you feel your account has been compromised, you should also update your password.',
        'list_description' => 'This is a list of devices that have logged into your account. Logout any devices that you do not recognize.',
        'unknown_platform' => 'Unknown',
        'unknown_browser' => 'Unknown',
        'ip_info_tooltip' => 'Lookup the geolocation of this IP address.',
        'current_device' => 'This device',
        'last_activity' => 'Last active :time',

        'password_input_label' => 'Your password',
        'password_input_helper' => 'Your password is required to force a logout of sessions that may have the remember cookie set on them.',

        'actions' => [

            'revoke' => [
                'trigger' => 'Logout device',
                'success' => 'The device was logged out.',
                'submit_button' => 'Logout device',
            ],

            'revoke_all' => [
                'trigger' => 'Logout all other devices',
                'success' => 'All other devices have been logged out.',
                'submit_button' => 'Logout all other devices',
                'modal_title' => 'Logout all other devices',
            ],

        ],
    ],
];
