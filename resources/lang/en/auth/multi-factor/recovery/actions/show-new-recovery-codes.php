<?php

declare(strict_types=1);

return [
    'modal' => [
        'heading' => 'New recovery codes',
        'description' => <<<'BLADE'
        Your recovery codes can be used to recover access to your account if your two-factor authentication device is lost. **Each code can only be used once.**
        <br><br>
        These codes will only be shown to you once, so be sure to store them somewhere safe like a password manager.
        BLADE,

        'actions' => [
            'copy' => [
                'label' => 'Copy',
            ],

            'download' => [
                'label' => 'Download',
            ],

            'submit' => [
                'label' => 'Done',
            ],
        ],

        'form' => [
            'confirm' => [
                'label' => 'I have saved my recovery codes and stored them securely',

                'messages' => [
                    'accepted' => 'You must store your recovery codes before continuing.',
                ],
            ],
        ],
    ],

    'messages' => [
        'copied' => 'Copied',
    ],
];
