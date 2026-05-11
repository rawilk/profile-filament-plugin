<?php

declare(strict_types=1);

return [
    'label' => 'Skonfiguruj',
    'another-label' => 'Skonfiguruj kolejną',

    'modal' => [
        'heading' => 'Skonfiguruj aplikację uwierzytelniającą',

        'description' => <<<'BLADE'
        Do ukończenia tego procesu potrzebna będzie aplikacja uwierzytelniająca lub rozszerzenie przeglądarki, takie jak <x-filament::link href="https://support.1password.com/one-time-passwords/" target="_blank">1Password</x-filament::link> lub Google Authenticator (<x-filament::link href="https://itunes.apple.com/us/app/google-authenticator/id388497605" target="_blank">iOS</x-filament::link>, <x-filament::link href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</x-filament::link>).
        BLADE,

        'content' => [
            'qr-code' => [
                'title' => 'Zeskanuj kod QR',
                'instruction' => 'Zeskanuj poniższy kod QR lub ręcznie wprowadź tajny klucz do swojej aplikacji uwierzytelniającej.',
                'alt' => 'Kod QR do zeskanowania za pomocą aplikacji uwierzytelniającej',
            ],

            'text-code' => [
                'title' => 'Nie możesz zeskanować kodu QR?',
                'instruction' => 'Zamiast tego wprowadź ten sekret:',

                'actions' => [
                    'copy' => [
                        'label' => 'Kopiuj kod',
                        'copied' => 'Skopiowano',
                    ],
                ],
            ],
        ],

        'form' => [
            'steps' => [
                'app' => [
                    'label' => 'Rejestracja aplikacji',
                ],

                'name' => [
                    'label' => 'Nazwa',
                ],
            ],

            'code' => [
                'title' => 'Pobierz kod weryfikacyjny',
                'instruction' => 'Wprowadź 6-cyfrowy kod, który widzisz w swojej aplikacji uwierzytelniającej.',

                'label' => 'Wprowadź kod weryfikacyjny',

                'validation-attribute' => 'kod',

                'below-content' => 'Będziesz musiał wprowadzać 6-cyfrowy kod z aplikacji uwierzytelniającej przy każdym logowaniu lub wykonywaniu wrażliwych czynności.',

                'messages' => [
                    'invalid' => 'Wprowadzony kod jest nieprawidłowy.',
                    'throttled' => 'Zbyt wiele prób. Spróbuj ponownie później.',
                ],
            ],

            'name' => [
                'label' => 'Nazwa aplikacji',

                'help' => 'Możesz nadać aplikacji znaczącą nazwę, aby móc ją później zidentyfikować.',

                'placeholder' => '1Password',

                'default-name' => 'Aplikacja uwierzytelniająca',
            ],
        ],

        'actions' => [
            'submit' => [
                'label' => 'Włącz aplikację uwierzytelniającą',
            ],
        ],
    ],

    'notifications' => [
        'enabled' => [
            'title' => 'Aplikacja uwierzytelniająca została włączona.',
        ],
    ],
];
