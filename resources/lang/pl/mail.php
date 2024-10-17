<?php

declare(strict_types=1);

return [
    'pending_email_verification' => [
        'subject' => 'Zweryfikuj swój adres e-mail',
        'greeting' => 'Witaj,',
        'line1' => 'Złożono prośbę o zmianę adresu e-mail na Twoim koncie na :email. Kliknij poniższy przycisk, aby zweryfikować swój nowy adres e-mail.',
        'button' => 'Zweryfikuj nowy adres e-mail',
        'line2' => 'Uwaga: Ten link wygaśnie za :minutes minut.',
        'line3' => 'Jeśli nie aktualizowałeś swojego adresu e-mail, nie musisz podejmować żadnych działań.',
        'salutation' => 'Dziękujemy,<br>:app_name',
    ],

    'email_updated' => [
        'subject' => 'Adres e-mail został zaktualizowany',
        'greeting' => 'Witaj,',
        'line1' => 'Otrzymujesz tę wiadomość, ponieważ adres e-mail Twojego konta :app_name został niedawno zaktualizowany.',
        'line2' => 'Od teraz będziesz musiał używać adresu ":email" do logowania się na swoje konto.',
        'line3' => 'Jeśli to Ty dokonałeś tej zmiany, nie musisz podejmować żadnych dalszych działań.',
        'line4' => 'Jeśli nie zainicjowałeś tej zmiany, [kliknij ten link](:url), aby cofnąć zmianę. Ten link wygaśnie za :days dni.',
        'salutation' => 'Dziękujemy,<br>:app_name',
    ],

    'request_details' => [
        'heading' => '**Szczegóły żądania**',
        'ip' => 'Adres IP: :ip',
        'date' => 'Data: :date',
    ],

];
