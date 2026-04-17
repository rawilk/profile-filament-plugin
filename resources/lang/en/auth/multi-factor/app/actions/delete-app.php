<?php

declare(strict_types=1);

return [
    'label' => 'Delete authenticator app',
    'tooltip' => 'Delete `:name` app',

    'modal' => [
        'heading' => 'Delete Authenticator App',
        'content' => 'You will no longer be able to use the **\`:name\`** app as a second form of authentication.',
    ],

    'notifications' => [
        'deleted' => [
            'title' => 'Authenticator app was deleted.',
        ],
    ],
];
