<?php

declare(strict_types=1);

return [
    'verify-email-change' => [
        'subject' => 'Verify your email address',
        'action' => 'Verify New Email Address',

        'lines' => [
            'A request has been made on your account to change your email address to :email. Please click the button below to verify your new email address.',
            "Heads up — this link only works for :expire. After that, you'll need to request a new one to verify your email address.",
            'If you did not update your email address, no further action is required.',
        ],
    ],

    'notice-of-email-change-request' => [
        'subject' => 'Your email address is being changed',
        'action' => 'Block Email Change',

        'lines' => [
            'We received a request to change the email address associated with your account.',
            'Once verified, the new email address on your account will be: :email.',
            'You can block the change before it is verified by clicking the button below.',
            'If you did not make this request, please contact us immediately.',
        ],
    ],
];
