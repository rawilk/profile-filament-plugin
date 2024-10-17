<?php

declare(strict_types=1);

return [
    'title' => 'Hasło i uwierzytelnianie',

    'password' => [
        'title' => 'Zmień hasło',

        'form' => [
            'current_password' => 'Obecne hasło',
            'password' => 'Nowe hasło',
            'password_confirmation' => 'Potwierdź nowe hasło',
            'save_button' => 'Zaktualizuj hasło',
            'notification' => 'Hasło zaktualizowane!',
            'forgot_password_link' => 'Zapomniałem hasła',
            'form_info' => 'Uwaga: Zmiana hasła spowoduje wylogowanie Cię ze wszystkich innych urządzeń.',
        ],
    ],

    'mfa' => [
        'title' => 'Uwierzytelnianie dwuskładnikowe',
        'status_enabled' => 'Włączone',
        'status_disabled' => 'Nieaktywne',
        'description' => 'Uwierzytelnianie dwuskładnikowe dodaje dodatkową warstwę bezpieczeństwa do Twojego konta, wymagając więcej niż tylko hasła do zalogowania. Aby włączyć uwierzytelnianie dwuskładnikowe na swoim koncie, dodaj jedną lub więcej z poniższych metod dwuskładnikowych.',
        'methods_title' => 'Metody dwuskładnikowe',
        'recovery_title' => 'Opcje odzyskiwania',
        'method_configured' => 'Skonfigurowane',
        'method_registration_date' => '— zarejestrowano :date',
        'method_last_used_date' => 'Ostatnio użyte: :date',
        'method_never_used' => 'Nigdy',

        'app' => [
            'title' => 'Aplikacja uwierzytelniająca',
            'description' => 'Użyj aplikacji uwierzytelniającej lub rozszerzenia przeglądarki, aby otrzymywać kody uwierzytelniania dwuskładnikowego, gdy zostaniesz o to poproszony.',
            'device_count' => '{1} 1 aplikacja|{few} :count aplikacje|{other} :count aplikacji',
            'form_intro' => 'Aplikacje uwierzytelniające i rozszerzenia przeglądarki, takie jak [1Password](:one_password), [Authy](:authy), [Microsoft Authenticator](:microsoft) itp., generują jednorazowe hasła, które służą jako drugi składnik do weryfikacji Twojej tożsamości podczas logowania.',
            'scan_title' => 'Zeskanuj kod QR',
            'scan_instructions' => 'Użyj aplikacji uwierzytelniającej lub rozszerzenia przeglądarki, aby zeskanować poniższy kod QR.',
            'enter_code_instructions' => 'Jeśli nie możesz zeskanować kodu QR, możesz ręcznie wprowadzić swój tajny klucz do aplikacji uwierzytelniającej.',
            'code_confirmation_input' => 'Zweryfikuj kod z aplikacji',
            'code_confirmation_placeholder' => '6-cyfrowy kod',
            'device_name' => 'Nazwa urządzenia',
            'device_name_help' => 'Możesz nadać aplikacji znaczącą nazwę, aby móc ją później zidentyfikować.',
            'device_name_placeholder' => 'Authy',
            'default_device_name' => 'Aplikacja uwierzytelniająca',
            'code_verification_fail' => 'Weryfikacja kodu dwuskładnikowego nie powiodła się. Spróbuj ponownie.',
            'code_verification_pass' => 'Weryfikacja kodu dwuskładnikowego powiodła się.',
            'copy_secret_tooltip' => 'Skopiuj tajny klucz do schowka',
            'copy_secret_confirmation' => 'Skopiowano',
            'submit_code_confirmation' => 'Zapisz',
            'cancel_code_confirmation' => 'Anuluj',
            'add_button' => 'Dodaj',
            'add_another_app_button' => 'Zarejestruj nową aplikację',
            'show_button' => 'Edytuj',
            'hide_button' => 'Ukryj',

            'actions' => [
                'delete' => [
                    'trigger_tooltip' => 'Usuń aplikację',
                    'trigger_label' => 'Usuń :name',
                    'title' => 'Usuń aplikację uwierzytelniającą',
                    'confirm' => 'Usuń',
                    'description' => 'Nie będziesz już mógł używać aplikacji **:name** jako drugiej formy uwierzytelniania.',
                ],

                'edit' => [
                    'trigger_tooltip' => 'Edytuj nazwę aplikacji',
                    'trigger_label' => 'Edytuj :name',
                    'title' => 'Edytuj aplikację uwierzytelniającą',
                    'name' => 'Nazwa urządzenia',
                    'name_help' => 'Możesz nadać aplikacji znaczącą nazwę, aby móc ją później zidentyfikować.',
                    'success_message' => 'Aplikacja uwierzytelniająca została pomyślnie zaktualizowana.',
                ],
            ],
        ],

        'webauthn' => [
            'title' => 'Klucze bezpieczeństwa',
            'description' => 'Klucze bezpieczeństwa to urządzenia sprzętowe, które mogą być używane jako drugi składnik uwierzytelniania.',
            'device_count' => '{1} 1 klucz|{few} :count klucze|{other} :count kluczy',
            'add_button' => 'Dodaj',
            'show_button' => 'Edytuj',
            'hide_button' => 'Ukryj',

            'actions' => [
                'register' => [
                    'trigger' => 'Zarejestruj nowy klucz bezpieczeństwa',
                    'name' => 'Nazwa klucza',
                    'name_placeholder' => 'Wprowadź nazwę dla tego klucza bezpieczeństwa',
                    'prompt_trigger' => 'Dodaj',
                    'register_fail' => 'Rejestracja klucza bezpieczeństwa nie powiodła się.',
                    'retry_button' => 'Spróbuj ponownie',
                    'waiting' => 'Oczekiwanie na dane wejściowe z interakcji przeglądarki...',
                    'register_fail_notification' => 'Nie udało się zarejestrować Twojego klucza bezpieczeństwa w tym momencie. Spróbuj ponownie z innym urządzeniem.',
                    'success' => 'Klucz bezpieczeństwa został pomyślnie zarejestrowany.',
                ],

                'delete' => [
                    'trigger_tooltip' => 'Usuń klucz bezpieczeństwa',
                    'trigger_label' => 'Usuń :name',
                    'title' => 'Usuń klucz bezpieczeństwa',
                    'confirm' => 'Usuń',
                    'description' => 'Nie będziesz już mógł używać klucza bezpieczeństwa **:name** jako drugiej formy uwierzytelniania.',
                ],

                'edit' => [
                    'title' => 'Edytuj klucz bezpieczeństwa',
                    'trigger_tooltip' => 'Edytuj nazwę klucza bezpieczeństwa',
                    'trigger_label' => 'Edytuj :name',
                    'name' => 'Nazwa klucza',
                    'name_placeholder' => 'Wprowadź nazwę dla tego klucza bezpieczeństwa',
                    'success_message' => 'Klucz bezpieczeństwa został pomyślnie zaktualizowany.',
                ],
            ],
        ],

        'recovery_codes' => [
            'title' => 'Kody odzyskiwania',
            'mfa_disabled' => 'Musisz najpierw dodać metodę dwuskładnikową, zanim będziesz mógł zobaczyć kody odzyskiwania.',
            'description' => 'Kody odzyskiwania mogą być użyte do uzyskania dostępu do Twojego konta w przypadku utraty dostępu do urządzenia i niemożności otrzymania kodów uwierzytelniania dwuskładnikowego.',
            'show_button' => 'Pokaż',
            'hide_button' => 'Ukryj',
            'current_codes_title' => 'Twoje kody odzyskiwania',
            'recommendation' => 'Przechowuj swoje kody odzyskiwania tak bezpiecznie jak hasło. Zalecamy zapisanie ich w menedżerze haseł takim jak [1Password](:1password), [Authy](:authy) lub [Keeper](:keeper).',
            'warning' => '**Przechowuj swoje kody odzyskiwania w bezpiecznym miejscu.** Te kody są ostatnią deską ratunku w przypadku utraty dostępu do Twojego konta, jeśli stracisz hasło i drugi składnik uwierzytelniania. Jeśli nie będziesz mógł znaleźć tych kodów, **stracisz** dostęp do swojego konta.',
            'regenerated_warning' => '**Te nowe kody zastąpiły Twoje stare kody. Zapisz je w bezpiecznym miejscu.** Te kody są ostatnią deską ratunku w przypadku utraty dostępu do Twojego konta, jeśli stracisz hasło i drugi składnik uwierzytelniania. Jeśli nie będziesz mógł znaleźć tych kodów, **stracisz** dostęp do swojego konta.',

            'actions' => [
                'download' => [
                    'label' => 'Pobierz',
                ],

                'print' => [
                    'label' => 'Drukuj',
                    'print_page_description' => ':app_name kody odzyskiwania do uwierzytelniania dwuskładnikowego.',
                    'print_page_title' => 'Kody odzyskiwania',
                ],

                'copy' => [
                    'label' => 'Kopiuj',
                    'confirmation' => 'Skopiowano',
                ],

                'generate' => [
                    'heading' => 'Wygeneruj nowe kody odzyskiwania',
                    'description' => "Gdy wygenerujesz nowe kody odzyskiwania, musisz pobrać lub wydrukować nowe kody. **Twoje stare kody przestaną działać.**",
                    'button' => 'Wygeneruj nowe kody odzyskiwania',
                    'success_title' => 'Sukces!',
                    'success_message' => 'Nowe kody odzyskiwania do uwierzytelniania dwuskładnikowego zostały pomyślnie wygenerowane. Zachowaj je w bezpiecznym, trwałym miejscu i usuń poprzednie kody.',
                ],
            ],
        ],
    ],

    'passkeys' => [
        'title' => 'Passkeys',
        'empty_heading' => 'Logowanie bez hasła za pomocą passkeys',
        'empty_description' => "Passkeys to zamiennik hasła, który weryfikuje Twoją tożsamość za pomocą dotyku, rozpoznawania twarzy, hasła urządzenia lub kodu PIN.\n\nPasskeys mogą być używane do logowania jako prosta i bezpieczna alternatywa dla Twojego hasła i poświadczeń dwuskładnikowych.",
        'default_key_name' => 'Passkey',
        'unique_validation_error' => 'Masz już urządzenie o tej nazwie.',

        'list' => [
            'description' => 'Passkeys to zamiennik hasła, który weryfikuje Twoją tożsamość za pomocą dotyku, rozpoznawania twarzy, hasła urządzenia lub kodu PIN.',
        ],

        'actions' => [
            'add' => [
                'trigger' => 'Dodaj passkey',
                'modal_title' => 'Skonfiguruj uwierzytelnianie bez hasła',
                'intro' => 'Twoje urządzenie obsługuje passkeys, zamiennik hasła, który weryfikuje Twoją tożsamość za pomocą dotyku, rozpoznawania twarzy, hasła urządzenia lub kodu PIN.',
                'intro_line2' => 'Passkeys mogą być używane do logowania jako prosta i bezpieczna alternatywa dla Twojego hasła i poświadczeń dwuskładnikowych.',
                'prompt_button' => 'Dodaj passkey',
                'register_fail' => 'Rejestracja passkey nie powiodła się.',
                'register_fail_notification' => 'Nie udało się zarejestrować Twojego passkey w tym momencie. Spróbuj ponownie później.',
                'name_field' => 'Nazwa passkey',
                'name_field_placeholder' => 'iPhone',
                'mfa_disabled_notice' => '**Uwaga:** Dodanie passkey włączy również uwierzytelnianie dwuskładnikowe za pomocą kodów odzyskiwania na Twoim koncie, na wypadek gdybyś kiedykolwiek stracił dostęp do swojego passkey.',

                'success' => [
                    'title' => 'Rejestracja passkey powiodła się',
                    'description' => 'Od teraz możesz używać tego passkey do logowania się do :app_name.',
                ],
            ],

            'edit' => [
                'trigger_label' => 'Edytuj :name',
                'trigger_tooltip' => 'Edytuj nazwę passkey',
                'title' => 'Edytuj passkey',
                'name' => 'Nazwa passkey',
                'name_placeholder' => 'iPhone',
                'success_notification' => 'Passkey został pomyślnie zaktualizowany!',
            ],

            'delete' => [
                'trigger_label' => 'Usuń :name',
                'trigger_tooltip' => 'Usuń passkey',
                'title' => 'Usuń passkey',
                'confirm' => 'Usuń',
                'description' => "Czy na pewno chcesz usunąć swój passkey \`**:name**\`?\n\nUsuwając ten passkey, nie będziesz już mógł go używać do logowania się na swoje konto z żadnego z urządzeń, na których został zsynchronizowany.\n\n**Uwaga:** Możesz nadal widzieć ten passkey jako opcję podczas logowania, dopóki nie usuniesz go również z ustawień zarządzania hasłami przeglądarki, urządzenia lub powiązanego konta.",
            ],

            'upgrade' => [
                'trigger_label' => 'Ulepsz :name do passkey',
                'trigger_tooltip' => 'Ulepsz do passkey',
                'modal_title' => 'Ulepsz rejestrację klucza bezpieczeństwa do passkey',
                'intro' => 'Twój klucz bezpieczeństwa **\`:name\`** może zostać ulepszony do passkey.',
                'prompt_button' => 'Ulepsz do passkey',
                'cancel_upgrade' => 'Zarejestruj inny passkey',

                'success' => [
                    'title' => "Pomyślnie ulepszono ':name' do passkey",
                    'description' => "Od teraz możesz używać tego passkey do logowania się do :app_name. Usunęliśmy stary klucz bezpieczeństwa ':name'.",
                ],
            ],

        ],
    ],
];
