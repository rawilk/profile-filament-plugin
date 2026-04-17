<?php

declare(strict_types=1);

return [
    'label' => 'Generate new codes',

    'modal' => [
        'title' => 'Generate new recovery codes',
        'description' => <<<'BLADE'
        When you generate new recovery codes, you must download or print the new codes. **Your old codes won't work anymore.**
        BLADE,

        'actions' => [
            'confirm' => [
                'label' => 'Generate new codes',
            ],
        ],
    ],

    'notifications' => [
        'regenerated' => [
            'title' => 'New recovery codes have been generated',
        ],
    ],
];
