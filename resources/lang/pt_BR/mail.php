<?php

declare(strict_types = 1);

return [

    'pending_email_verification' => [
        'subject'    => 'Verifique seu endereço de e-mail',
        'greeting'   => 'Olá,',
        'line1'      => 'Foi feita uma solicitação na sua conta para alterar seu endereço de e-mail para :email. Por favor, clique no botão abaixo para verificar seu novo endereço de e-mail.',
        'button'     => 'Verificar novo endereço de e-mail',
        'line2'      => 'Nota: Este link expirará em :minutes minutos.',
        'line3'      => 'Se você não atualizou seu endereço de e-mail, nenhuma ação adicional é necessária.',
        'salutation' => 'Obrigado,<br>:app_name',
    ],

    'email_updated' => [
        'subject'    => 'Endereço de e-mail atualizado',
        'greeting'   => 'Olá,',
        'line1'      => 'Você está recebendo este e-mail porque o endereço de e-mail da sua conta :app_name foi atualizado recentemente.',
        'line2'      => 'A partir de agora, você precisará usar ":email" para entrar na sua conta.',
        'line3'      => 'Se foi você que fez esta alteração, nenhuma ação adicional é necessária.',
        'line4'      => 'Se você não iniciou esta alteração, [clique neste link](:url) para reverter a alteração. Este link expirará em :days dias.',
        'salutation' => 'Obrigado,<br>:app_name',
    ],

    'request_details' => [
        'heading' => '**Detalhes da solicitação**',
        'ip'      => 'Endereço IP: :ip',
        'date'    => 'Data: :date',
    ],

];
