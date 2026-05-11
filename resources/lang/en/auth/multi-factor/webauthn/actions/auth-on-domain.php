<?php

declare(strict_types=1);

return [
    'title' => 'Security key and passkey authentication',

    'form' => [
        'messages' => [
            'prompt' => 'When you are ready, click the Authenticate button to begin the authentication process.',

            'popups-disabled' => [
                'passkey' => 'Please allow popups for this site to use your passkey.',
                'webauthn' => 'Please allow popups for this site to use your security key or passkey.',
            ],
        ],

        'actions' => [
            'authenticate' => [
                'label' => 'Authenticate',
            ],
        ],
    ],
];
