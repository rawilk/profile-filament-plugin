<?php

declare(strict_types=1);

return [
    'title' => 'Sessions',

    'manager' => [
        'heading' => 'Web sessions',

        'description' => 'If necessary, you may also log out of all of your other browser sessions across all of your devices. If you feel your account has been compromised, you should also update your password.',

        'list' => [
            'description' => 'This is a list of devices that have logged into your account. Logout any devices that you do not recognize.',

            'unknown' => [
                'platform' => 'Unknown',
                'browser' => 'Unknown',
            ],

            'ip-info' => [
                'tooltip' => 'Lookup the geolocation of this IP address.',
            ],

            'current-device' => 'This device',

            'last-activity' => 'Last active :time',
        ],
    ],
];
