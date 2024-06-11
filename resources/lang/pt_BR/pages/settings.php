<?php

declare(strict_types=1);

return [
    'title' => 'Conta',

    'account_security_link' => 'Deseja gerenciar as configurações de segurança da conta? Você pode encontrá-las na página [Senha e autenticação](:url).',

    'email' => [
        'invalid_verification_link' => 'Este link de verificação já foi utilizado ou está expirado. Por favor, solicite um novo para verificar seu endereço de e-mail.',
        'email_already_taken' => 'O endereço de e-mail do seu link já está em uso.',
        'email_verified' => 'Seu novo endereço de e-mail foi verificado e agora pode ser usado para login.',
        'invalid_revert_link' => 'Este link já foi utilizado ou está expirado. Por favor, entre em contato com o suporte para mais assistência.',
        'email_reverted' => 'Seu endereço de e-mail foi revertido para o anterior e agora pode ser usado para login.',

        'heading' => 'Endereço de e-mail',
        'change_pending_badge' => 'Alteração pendente',
        'email_description' => 'Este e-mail será usado para login, notificações relacionadas à conta e também pode ser usado para redefinição de senha.',

        'pending_heading' => 'Confirme seu e-mail',
        'pending_description' => 'Precisamos que você verifique seu e-mail **:email** e clique no link de verificação que enviamos para confirmar que é você e concluir a atualização. Sua alteração não terá efeito até que você confirme seu novo e-mail.',

        'actions' => [

            'edit' => [
                'trigger' => 'Alterar e-mail',
                'modal_title' => 'Editar endereço de e-mail',
                'email_label' => 'Novo endereço de e-mail',
                'email_placeholder' => 'exemplo@:host',
                'email_help' => 'Enviaremos um e-mail para este endereço para verificar se você tem acesso a ele. Suas alterações não terão efeito até que você verifique o novo endereço de e-mail.',
                'success_title' => 'Sucesso!',
                'success_body' => 'Seu endereço de e-mail foi atualizado.',
                'success_body_pending' => 'Verifique seu novo endereço de e-mail para um link de verificação.',
            ],

            'resend' => [
                'trigger' => 'Reenviar e-mail',
                'success_title' => 'Sucesso!',
                'success_body' => 'Um novo link de verificação foi enviado para seu novo endereço de e-mail.',

                'throttled' => [
                    'title' => 'Muitas solicitações',
                    'body' => 'Por favor, tente novamente em :minutes minutos.',
                ],
            ],

            'cancel' => [
                'trigger' => 'Desfazer alteração de e-mail',
            ],

        ],
    ],

    'delete_account' => [
        'title' => 'Excluir conta',
        'description' => 'Uma vez que você exclua sua conta, todos os seus dados e recursos serão permanentemente deletados. Não seremos capazes de recuperar nenhum dos seus dados.',

        'actions' => [

            'delete' => [
                'trigger' => 'Excluir sua conta',
                'modal_title' => 'Excluir sua conta',
                'submit_button' => 'Excluir sua conta',
                'email_label' => 'Para confirmar, digite seu e-mail, ":email", na caixa abaixo',
                'incorrect_email' => 'O endereço de e-mail que você digitou está incorreto.',
                'success' => 'Sua conta foi excluída.',
            ],

        ],
    ],
];
