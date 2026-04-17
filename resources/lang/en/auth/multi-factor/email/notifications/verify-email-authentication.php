<?php

declare(strict_types=1);

return [
    'subject' => 'Email Authentication Code',

    'greeting' => "Here's your email authentication verification code",

    'lines' => [
        'Your email verification code is:',
        '**:code**',
        'This code can only be used once. It will expire in :minutes minutes.',
        "**Your security is our priority:** DO NOT SHARE YOUR CODE. We will never contact you to ask for it. If you didn't request this code, please change your password to help keep your account secure.",
    ],
];
