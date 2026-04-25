<?php

declare(strict_types=1);

return [
    'label' => 'Delete security key',
    'tooltip' => 'Delete `:name` security key',

    'modal' => [
        'heading' => 'Delete Security Key',
        'description' => 'You will no longer be able to use the **\`:name\`** security key as a second form of authentication.',
    ],

    'notifications' => [
        'deleted' => [
            'title' => 'Security key was deleted.',
        ],
    ],
];
