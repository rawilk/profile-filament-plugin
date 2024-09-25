<?php

declare(strict_types=1);

return [
    'pending_email_verification' => [
        'subject' => 'Verify your email address',
        'greeting' => 'Hello,',
        'line1' => 'A request has been made on your account to change your email address to :email. Please click the button below to verify your new email address.',
        'button' => 'Verify new email address',
        'line2' => 'Note: This link will expire in :minutes minutes.',
        'line3' => 'If you did not update your email address, no further action is required.',
        'salutation' => 'Thanks,<br>:app_name',
    ],

    'email_updated' => [
        'subject' => 'Email address updated',
        'greeting' => 'Hello,',
        'line1' => 'You are receiving this email because your :app_name account email address was recently updated.',
        'line2' => 'From now on, you will need to use ":email" to sign into your account.',
        'line3' => 'If this was you, no further action is required.',
        'line4' => 'If you did not initiate this change, [click this link](:url) to revert the change. This link will expire in :days days.',
        'salutation' => 'Thanks,<br>:app_name',
    ],

    'request_details' => [
        'heading' => '**Request details**',
        'ip' => 'IP address: :ip',
        'date' => 'Date: :date',
    ],

];
