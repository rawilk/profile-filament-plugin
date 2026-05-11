<?php

declare(strict_types=1);

return [
    'title' => 'Weryfikacja dwuetapowa',

    'heading' => 'Zweryfikuj swoją tożsamość',

    'subheading' => 'Aby zapewnić bezpieczeństwo Twojego konta, chcemy mieć pewność, że to naprawdę Ty próbujesz się zalogować.',

    'form' => [
        'provider' => [
            'heading' => 'Wybierz sposób weryfikacji tożsamości:',
        ],
    ],

    'actions' => [
        'change-provider' => [
            'label' => 'Wypróbuj inny sposób',
        ],
    ],

    'messages' => [
        'password-confirmation-expired' => 'Potwierdź ponownie swoje hasło, aby wznowić uwierzytelnianie wieloskładnikowe.',
    ],
];
