<?php

declare(strict_types=1);

return [
    'alert' => [
        'dismiss' => 'Dispensar',
    ],

    'mfa_challenge' => [
        'invalid_challenged_user' => 'Não foi possível verificar sua conta de usuário.',
    ],

    'sudo_challenge' => [

        'title' => 'Confirmar acesso',
        'tip' => '**Dica:** Você está entrando no modo sudo. Após realizar uma ação protegida por sudo, você só será solicitado a reautenticar novamente após algumas horas de inatividade.',
        'cancel_button' => 'Cancelar',
        'signed_in_as' => 'Logado como: **:handle**',
        'expired' => 'Sua sessão sudo expirou. Por favor, atualize a página para tentar novamente.',

        'alternative_heading' => 'Tendo problemas?',

        'totp' => [
            'use_label' => 'Use seu aplicativo autenticador',
            'heading' => 'Código de autenticação',
            'help_text' => 'Abra seu aplicativo ou extensão de navegador de autenticação de dois fatores (TOTP) para ver seu código de autenticação.',
            'placeholder' => 'Código de 6 dígitos',
            'invalid' => 'O código que você inseriu é inválido.',
            'submit' => 'Verificar',
        ],

        'webauthn' => [
            'use_label' => 'Use sua chave de segurança',
            'use_label_including_passkeys' => 'Use sua passkey ou chave de segurança',
            'heading' => 'Chave de segurança',
            'heading_including_passkeys' => 'Passkey ou chave de segurança',
            'waiting' => 'Aguardando entrada da interação do navegador...',
            'failed' => 'Autenticação falhou.',
            'retry' => 'Tentar novamente chave de segurança',
            'retry_including_passkeys' => 'Tentar novamente passkey ou chave de segurança',
            'submit' => 'Usar chave de segurança',
            'submit_including_passkeys' => 'Usar passkey ou chave de segurança',
            'hint' => 'Quando estiver pronto, autentique usando o botão abaixo.',
            'invalid' => 'Autenticação falhou.',
        ],

        'password' => [
            'use_label' => 'Use sua senha',
            'input_label' => 'Sua senha',
            'submit' => 'Confirmar',
            'invalid' => 'Senha incorreta.',
        ],

    ],

    'masked_value' => [
        'reveal_button' => 'Revelar',
    ],

];
