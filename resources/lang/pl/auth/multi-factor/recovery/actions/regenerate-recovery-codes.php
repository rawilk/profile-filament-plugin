<?php

declare(strict_types=1);

return [
    'label' => 'Generuj nowe kody',

    'modal' => [
        'title' => 'Generuj nowe kody odzyskiwania',
        'description' => <<<'BLADE'
        Po wygenerowaniu nowych kodów odzyskiwania należy je pobrać lub wydrukować. **Twoje stare kody nie będą już działać.**
        BLADE,

        'actions' => [
            'confirm' => [
                'label' => 'Generuj nowe kody',
            ],
        ],
    ],

    'notifications' => [
        'regenerated' => [
            'title' => 'Nowe kody odzyskiwania zostały wygenerowane',
        ],
    ],
];
