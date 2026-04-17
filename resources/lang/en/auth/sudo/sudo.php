<?php

declare(strict_types=1);

return [
    'challenge' => [
        'title' => 'Confirm access',

        'heading' => 'Confirm access',

        'alternate-options' => 'Having problems?',

        'signed-in-as' => [
            'content' => 'Signed in as: **:handle**',
        ],

        'tip' => <<<'BLADE'
        **Tip:** You are entering sudo mode. After you've performed a sudo-protected action, you'll only be asked to re-authenticate again after a few hours of inactivity.
        BLADE,
    ],

    'messages' => [
        'expired' => 'Your sudo session has expired.',
    ],

    'notifications' => [
        'throttled' => [
            'title' => 'Too many attempts',
            'body' => 'Please try again in :seconds seconds.',
        ],
    ],
];
