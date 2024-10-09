<?php

declare(strict_types=1);

return [
    'title' => 'Senha e autenticação',

    'password' => [
        'title' => 'Alterar senha',

        'form' => [
            'current_password' => 'Senha atual',
            'password' => 'Nova senha',
            'password_confirmation' => 'Confirmar nova senha',
            'save_button' => 'Atualizar senha',
            'notification' => 'Senha atualizada!',
            'forgot_password_link' => 'Esqueci minha senha',
            'form_info' => 'Nota: Alterar sua senha irá desconectá-lo de todos os seus outros dispositivos.',
        ],
    ],

    'mfa' => [
        'title' => 'Autenticação de dois fatores',
        'status_enabled' => 'Ativada',
        'status_disabled' => 'Inativa',
        'description' => 'A autenticação de dois fatores adiciona uma camada adicional de segurança à sua conta, exigindo mais do que apenas uma senha para entrar. Para ativar a autenticação de dois fatores na sua conta, adicione um ou mais dos métodos de dois fatores abaixo.',
        'methods_title' => 'Métodos de dois fatores',
        'recovery_title' => 'Opções de recuperação',
        'method_configured' => 'Configurado',
        'method_registration_date' => '— registrado em :date',
        'method_last_used_date' => 'Último uso: :date',
        'method_never_used' => 'Nunca',

        'app' => [
            'title' => 'Aplicativo autenticador',
            'description' => 'Use um aplicativo de autenticação ou extensão de navegador para obter códigos de autenticação de dois fatores quando solicitado.',
            'device_count' => ':count aplicativo|:count aplicativos',
            'form_intro' => 'Aplicativos autenticadores e extensões de navegador como [1Password](:one_password), [Authy](:authy), [Microsoft Authenticator](:microsoft), etc. geram senhas únicas que são usadas como um segundo fator para verificar sua identidade quando solicitado durante o login.',
            'scan_title' => 'Escaneie o código QR',
            'scan_instructions' => 'Use um aplicativo autenticador ou extensão de navegador para escanear o código QR abaixo.',
            'enter_code_instructions' => 'Se você não conseguir escanear o código QR, pode inserir manualmente sua chave secreta no seu aplicativo autenticador.',
            'code_confirmation_input' => 'Verificar o código do aplicativo',
            'code_confirmation_placeholder' => 'Código de 6 dígitos',
            'device_name' => 'Nome do dispositivo',
            'device_name_help' => 'Você pode dar ao aplicativo um nome significativo para identificá-lo mais tarde.',
            'device_name_placeholder' => 'Authy',
            'default_device_name' => 'Aplicativo autenticador',
            'code_verification_fail' => 'A verificação do código de dois fatores falhou. Por favor, tente novamente.',
            'code_verification_pass' => 'A verificação do código de dois fatores foi bem-sucedida.',
            'copy_secret_tooltip' => 'Copiar segredo para a área de transferência',
            'copy_secret_confirmation' => 'Copiado',
            'submit_code_confirmation' => 'Salvar',
            'cancel_code_confirmation' => 'Cancelar',
            'add_button' => 'Adicionar',
            'add_another_app_button' => 'Registrar novo aplicativo',
            'show_button' => 'Editar',
            'hide_button' => 'Ocultar',

            'actions' => [

                'delete' => [
                    'trigger_tooltip' => 'Remover aplicativo',
                    'trigger_label' => 'Excluir :name',
                    'title' => 'Excluir Aplicativo Autenticador',
                    'confirm' => 'Excluir',
                    'description' => 'Você não poderá mais usar o aplicativo **:name** como um segundo método de autenticação.',
                ],

                'edit' => [
                    'trigger_tooltip' => 'Editar nome do aplicativo',
                    'trigger_label' => 'Editar :name',
                    'title' => 'Editar Aplicativo Autenticador',
                    'name' => 'Nome do dispositivo',
                    'name_help' => 'Você pode dar ao aplicativo um nome significativo para identificá-lo mais tarde.',
                    'success_message' => 'Aplicativo autenticador atualizado com sucesso.',
                ],

            ],
        ],

        'webauthn' => [
            'title' => 'Chaves de segurança',
            'description' => 'Chaves de segurança são dispositivos de hardware que podem ser usados como seu segundo fator de autenticação.',
            'device_count' => ':count chave|:count chaves',
            'add_button' => 'Adicionar',
            'show_button' => 'Editar',
            'hide_button' => 'Ocultar',

            'actions' => [

                'register' => [
                    'trigger' => 'Registrar nova chave de segurança',
                    'name' => 'Nome da chave',
                    'name_placeholder' => 'Digite um apelido para esta chave de segurança',
                    'prompt_trigger' => 'Adicionar',
                    'register_fail' => 'Falha no registro da chave de segurança.',
                    'retry_button' => 'Tentar novamente',
                    'waiting' => 'Aguardando interação do navegador...',
                    'register_fail_notification' => 'Não conseguimos registrar sua chave de segurança no momento. Por favor, tente novamente com um dispositivo diferente.',
                    'success' => 'Chave de segurança registrada com sucesso.',
                ],

                'delete' => [
                    'trigger_tooltip' => 'Remover chave de segurança',
                    'trigger_label' => 'Excluir :name',
                    'title' => 'Excluir Chave de Segurança',
                    'confirm' => 'Excluir',
                    'description' => 'Você não poderá mais usar a chave de segurança **:name** como um segundo método de autenticação.',
                ],

                'edit' => [
                    'title' => 'Editar Chave de Segurança',
                    'trigger_tooltip' => 'Editar nome da chave de segurança',
                    'trigger_label' => 'Editar :name',
                    'name' => 'Nome da chave',
                    'name_placeholder' => 'Digite um apelido para esta chave de segurança',
                    'success_message' => 'Chave de segurança atualizada com sucesso.',
                ],

            ],
        ],

        'recovery_codes' => [
            'title' => 'Códigos de recuperação',
            'mfa_disabled' => 'Você deve primeiro adicionar um método de dois fatores antes de visualizar os códigos de recuperação.',
            'description' => 'Os códigos de recuperação podem ser usados para acessar sua conta no caso de você perder o acesso ao seu dispositivo e não puder receber os códigos de autenticação de dois fatores.',
            'show_button' => 'Ver',
            'hide_button' => 'Ocultar',
            'current_codes_title' => 'Seus códigos de recuperação',
            'recommendation' => 'Mantenha seus códigos de recuperação tão seguros quanto sua senha. Recomendamos salvá-los com um gerenciador de senhas, como [1Password](:1password), [Authy](:authy) ou [Keeper](:keeper).',
            'warning' => '**Guarde seus códigos de recuperação em um lugar seguro.** Esses códigos são o último recurso para acessar sua conta caso você perca sua senha e fatores secundários. Se você não encontrar esses códigos, você **perderá** o acesso à sua conta.',
            'regenerated_warning' => '**Esses novos códigos substituíram seus antigos códigos. Salve-os em um local seguro.** Esses códigos são o último recurso para acessar sua conta caso você perca sua senha e fatores secundários. Se você não encontrar esses códigos, você **perderá** o acesso à sua conta.',

            'actions' => [

                'download' => [
                    'label' => 'Baixar',
                ],

                'print' => [
                    'label' => 'Imprimir',
                    'print_page_description' => 'Códigos de recuperação da conta de autenticação de dois fatores do :app_name.',
                    'print_page_title' => 'Códigos de recuperação',
                ],

                'copy' => [
                    'label' => 'Copiar',
                    'confirmation' => 'Copiado',
                ],

                'generate' => [
                    'heading' => 'Gerar novos códigos de recuperação',
                    'description' => 'Quando você gerar novos códigos de recuperação, você deve baixar ou imprimir os novos códigos. **Seus códigos antigos não funcionarão mais.**',
                    'button' => 'Gerar novos códigos de recuperação',
                    'success_title' => 'Sucesso!',
                    'success_message' => 'Novos códigos de recuperação de dois fatores gerados com sucesso. Salve-os em um local seguro e duradouro e descarte seus códigos anteriores.',
                ],

            ],
        ],
    ],

    'passkeys' => [
        'title' => 'Chaves de acesso',
        'empty_heading' => 'Login sem senha com chaves de acesso',
        'empty_description' => "As chaves de acesso são uma substituição de senha que valida sua identidade usando toque, reconhecimento facial, senha do dispositivo ou PIN.\n\nAs chaves de acesso podem ser usadas para login como uma alternativa simples e segura à sua senha e credenciais de dois fatores.",
        'default_key_name' => 'Chave de acesso',
        'unique_validation_error' => 'Você já tem um dispositivo com este nome.',

        'list' => [
            'description' => 'As chaves de acesso são uma substituição de senha que valida sua identidade usando toque, reconhecimento facial, senha do dispositivo ou PIN.',
        ],

        'actions' => [

            'add' => [
                'trigger' => 'Adicionar uma chave de acesso',
                'modal_title' => 'Configurar autenticação sem senha',
                'intro' => 'Seu dispositivo suporta chaves de acesso, uma substituição de senha que valida sua identidade usando toque, reconhecimento facial, senha do dispositivo ou PIN.',
                'intro_line2' => 'As chaves de acesso podem ser usadas para login como uma alternativa simples e segura à sua senha e credenciais de dois fatores.',
                'prompt_button' => 'Adicionar chave de acesso',
                'register_fail' => 'Falha no registro da chave de acesso.',
                'register_fail_notification' => 'Não conseguimos registrar sua chave de acesso no momento. Por favor, tente novamente mais tarde.',
                'name_field' => 'Apelido da chave de acesso',
                'name_field_placeholder' => 'iPhone',
                'mfa_disabled_notice' => '**Nota:** Adicionar uma chave de acesso também habilitará a autenticação de dois fatores via códigos de recuperação na sua conta caso você perca o acesso à sua chave de acesso.',

                'success' => [
                    'title' => 'Registro de chave de acesso bem-sucedido',
                    'description' => 'A partir de agora, você pode usar esta chave de acesso para fazer login no :app_name.',
                ],
            ],

            'edit' => [
                'trigger_label' => 'Editar :name',
                'trigger_tooltip' => 'Editar apelido da chave de acesso',
                'title' => 'Editar chave de acesso',
                'name' => 'Apelido da chave de acesso',
                'name_placeholder' => 'iPhone',
                'success_notification' => 'Chave de acesso atualizada com sucesso!',
            ],

            'delete' => [
                'trigger_label' => 'Excluir :name',
                'trigger_tooltip' => 'Excluir chave de acesso',
                'title' => 'Excluir chave de acesso',
                'confirm' => 'Excluir',
                'description' => "Tem certeza de que deseja excluir sua chave de acesso **:name**?\n\nAo remover essa chave de acesso, você não poderá mais usá-la para fazer login na sua conta de nenhum dos dispositivos em que ela foi sincronizada.\n\n**Nota:** Você pode continuar vendo esta chave de acesso como uma opção durante o login até excluí-la também do seu navegador, dispositivo ou configurações de gerenciamento de senhas da conta associada.",
            ],

            'upgrade' => [
                'trigger_label' => 'Atualizar :name para uma chave de acesso',
                'trigger_tooltip' => 'Atualizar para chave de acesso',
                'modal_title' => 'Atualizar o registro da sua chave de segurança para uma chave de acesso',
                'intro' => 'Sua chave de segurança **:name** pode ser atualizada para uma chave de acesso.',
                'prompt_button' => 'Atualizar para chave de acesso',
                'cancel_upgrade' => 'Registrar uma chave de acesso diferente',

                'success' => [
                    'title' => "Atualização bem-sucedida de ':name' para uma chave de acesso",
                    'description' => "A partir de agora, você pode usar esta chave de acesso para fazer login no :app_name. Excluímos a antiga chave de segurança ':name'.",
                ],
            ],

        ],
    ],
];
