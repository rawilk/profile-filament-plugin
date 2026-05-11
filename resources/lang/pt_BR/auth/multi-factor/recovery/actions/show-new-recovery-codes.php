<?php

declare(strict_types=1);

return [
    'modal' => [
        'heading' => 'Novos códigos de recuperação',
        'description' => <<<'BLADE'
        Seus códigos de recuperação podem ser usados para recuperar o acesso à sua conta se o seu dispositivo de autenticação de dois fatores for perdido. **Cada código só pode ser usado uma vez.**
        <br><br>
        Esses códigos só serão mostrados a você uma vez, portanto, certifique-se de guardá-los em algum lugar seguro, como um gerenciador de senhas.
        BLADE,

        'actions' => [
            'copy' => [
                'label' => 'Copiar',
            ],

            'download' => [
                'label' => 'Baixar',
            ],

            'submit' => [
                'label' => 'Concluído',
            ],
        ],

        'form' => [
            'confirm' => [
                'label' => 'Salvei meus códigos de recuperação e os armazenei com segurança',

                'messages' => [
                    'accepted' => 'Você deve armazenar seus códigos de recuperação antes de continuar.',
                ],
            ],
        ],
    ],

    'messages' => [
        'copied' => 'Copiado',
    ],
];
