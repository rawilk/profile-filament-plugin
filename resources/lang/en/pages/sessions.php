<?php

declare(strict_types=1);

return [
    'title' => 'Sessions',

    'manager' => [
        'heading' => 'Web sessions',
        'description' => 'If necessary, you may also log out of all of your other browser sessions across all of your devices. If you feel your account has been compromised, you should also update your password.',
        'list_description' => 'This is a list of devices that have logged into your account. Revoke any sessions that you do not recognize.',
        'unknown_platform' => 'Unknown',
        'unknown_browser' => 'Unknown',
        'ip_info_tooltip' => 'Lookup the geolocation of this IP address.',
        'current_device' => 'This device',
        'last_activity' => 'Last active :time',

        'password_input_label' => 'Your password',
        'password_input_helper' => 'Your password is required to force a logout of sessions that may have the remember cookie set on them.',

        'actions' => [

            'revoke' => [
                'trigger' => 'Revoke session',
                'success' => 'Session was revoked.',
                'submit_button' => 'Revoke session',
            ],

            'revoke_all' => [
                'trigger' => 'Revoke all other sessions',
                'success' => 'All other sessions have been revoked.',
                'submit_button' => 'Revoke all other sessions',
                'modal_title' => 'Revoke all other sessions',
            ],

        ],
    ],
];
