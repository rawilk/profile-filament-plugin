<?php

declare(strict_types=1);

return [
    // Título da página de fallback
    'heading' => 'Autenticação de dois fatores',

    // Título alternativo de fallback
    'alternative_heading' => 'Tendo problemas?',

    'totp' => [
        'heading' => 'Autenticação de dois fatores',
        'label' => 'Código de autenticação',
        'placeholder' => 'Código de 6 dígitos',
        'hint' => 'Abra seu aplicativo ou extensão de navegador de autenticação de dois fatores (TOTP) para ver seu código de autenticação.',
        'alternative_heading' => 'Não consegue verificar com seu aplicativo autenticador?',
        'use_label' => 'Use seu aplicativo autenticador',
        'invalid' => 'O código que você inseriu não é válido.',
    ],

    'recovery_code' => [
        'heading' => 'Recuperação de dois fatores',
        'label' => 'Código de recuperação',
        'placeholder' => 'XXXXX-XXXXX',
        'hint' => 'Se você não conseguir acessar seu dispositivo móvel, insira um de seus códigos de recuperação para verificar sua identidade.',
        'alternative_heading' => 'Não tem um código de recuperação?',
        'use_label' => 'Use um código de recuperação',
        'invalid' => 'O código que você inseriu não é válido.',
    ],

    'webauthn' => [
        'heading' => 'Autenticação de dois fatores',
        'label' => 'Chave de segurança',
        'label_including_passkeys' => 'Chave de acesso ou chave de segurança',
        'hint' => 'Quando estiver pronto, autentique usando o botão abaixo.',
        'alternative_heading' => 'Não consegue verificar com sua chave de segurança?',
        'use_label' => 'Use sua chave de segurança',
        'use_label_including_passkeys' => 'Use sua chave de acesso ou chave de segurança',
        'waiting' => 'Aguardando interação do navegador...',
        'failed' => 'Falha na autenticação.',
        'retry' => 'Tentar novamente a chave de segurança',
        'retry_including_passkeys' => 'Tentar novamente a chave de acesso ou chave de segurança',
        'passkey_login_button' => 'Entrar com uma chave de acesso',

        'assert' => [
            'failure_title' => 'Erro',
            'failure' => 'Não conseguimos verificar sua identidade com esta chave. Por favor, tente uma chave diferente ou um método de autenticação diferente para verificar sua identidade.',
            'passkey_required' => 'Esta chave não pode ser usada para autenticação de chave de acesso.',
        ],

        'unsupported' => [
            'title' => 'Seu navegador não é compatível!',
            'message' => 'Parece que seu navegador ou dispositivo não é compatível com chaves de segurança WebAuthn. Você pode usar um dos seus outros métodos de dois fatores ou tentar mudar para um navegador compatível.',
            'learn_more_link' => 'Saiba mais',
        ],
    ],

    'actions' => [
        'authenticate' => 'Verificar',
        'webauthn' => 'Usar chave de segurança',
        'webauthn_including_passkeys' => 'Usar chave de acesso ou chave de segurança',
    ],

];
