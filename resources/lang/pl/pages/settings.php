<?php

declare(strict_types=1);

return [
    'title' => 'Konto',

    'account_security_link' => 'Chcesz zarządzać ustawieniami bezpieczeństwa konta? Znajdziesz je na stronie [Hasło i uwierzytelnianie](:url).',

    'email' => [
        'invalid_verification_link' => 'Ten link weryfikacyjny został już wykorzystany lub wygasł. Poproś o nowy link, aby zweryfikować swój adres e-mail.',
        'email_already_taken' => 'Adres e-mail z Twojego linku jest już zajęty.',
        'email_verified' => 'Twój nowy adres e-mail został zweryfikowany i może być teraz używany do logowania.',
        'invalid_revert_link' => 'Ten link został już wykorzystany lub wygasł. Skontaktuj się z naszym wsparciem w celu uzyskania dalszej pomocy.',
        'email_reverted' => 'Twój adres e-mail został przywrócony do poprzedniego stanu i może być teraz używany do logowania.',

        'heading' => 'Adres e-mail',
        'label' => 'E-mail',
        'change_pending_badge' => 'Oczekuje na zmianę',
        'email_description' => 'Ten adres e-mail będzie używany do logowania, powiadomień związanych z kontem i może być również używany do resetowania hasła.',

        'pending_heading' => 'Potwierdź swój e-mail',
        'pending_description' => 'Musisz tylko sprawdzić swoją skrzynkę e-mail **:email** i kliknąć link weryfikacyjny, który Ci wysłaliśmy, aby potwierdzić, że to Ty i zakończyć aktualizację. Twoja zmiana nie wejdzie w życie, dopóki nie potwierdzisz nowego adresu e-mail.',

        'actions' => [

            'edit' => [
                'trigger' => 'Zmień e-mail',
                'modal_title' => 'Edytuj adres e-mail',
                'email_label' => 'Nowy adres e-mail',
                'email_placeholder' => 'example@:host',
                'email_help' => 'Wyślemy e-mail na ten adres, aby zweryfikować, że masz do niego dostęp. Twoje zmiany nie wejdą w życie, dopóki nie zweryfikujesz nowego adresu e-mail.',
                'success_title' => 'Sukces!',
                'success_body' => 'Twój adres e-mail został zaktualizowany.',
                'success_body_pending' => 'Sprawdź swój nowy adres e-mail, aby znaleźć link weryfikacyjny.',
            ],

            'resend' => [
                'trigger' => 'Wyślij ponownie e-mail',
                'success_title' => 'Sukces!',
                'success_body' => 'Nowy link weryfikacyjny został wysłany na Twój nowy adres e-mail.',

                'throttled' => [
                    'title' => 'Zbyt wiele żądań',
                    'body' => 'Spróbuj ponownie za :minutes minut.',
                ],
            ],

            'cancel' => [
                'trigger' => 'Cofnij zmianę e-maila',
            ],

        ],
    ],

    'delete_account' => [
        'title' => 'Usuń konto',
        'description' => 'Po usunięciu konta wszystkie Twoje dane i zasoby zostaną trwale usunięte. Nie będziemy w stanie odzyskać żadnych Twoich danych.',

        'actions' => [

            'delete' => [
                'trigger' => 'Usuń swoje konto',
                'modal_title' => 'Usuń swoje konto',
                'submit_button' => 'Usuń swoje konto',
                'email_label' => 'Aby potwierdzić, wpisz swój adres e-mail, ":email", w polu poniżej',
                'incorrect_email' => 'Wprowadzony adres e-mail jest niepoprawny.',
                'success' => 'Twoje konto zostało usunięte.',
            ],

        ],
    ],
];
