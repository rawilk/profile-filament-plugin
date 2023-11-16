<?php

declare(strict_types=1);

return [

    'alert' => [
        'dismiss' => 'Dismiss',
    ],

    'blocked_profile_section' => [

        'title' => 'Section locked',
        'update_password' => 'Your account is not able to access the update password feature at this time.',
        'mfa' => 'Two-factor authentication functionality is not available to your account at this time.',
        'passkeys' => 'Passkey functionality is not available to your account at this time.',

    ],

    'sudo_challenge' => [

        'title' => 'Confirm access',
        'tip' => "**Tip:** You are entering sudo mode. After you've performed a sudo-protected action, you'll only be asked to re-authenticate again after a few hours of inactivity.",
        'cancel_button' => 'Cancel',
        'signed_in_as' => 'Signed in as: **:handle**',

        'alternative_heading' => 'Having problems?',

        'totp' => [
            'use_label' => 'Use your authenticator app',
            'heading' => 'Authentication code',
            'help_text' => 'Open your two-factor authenticator (TOTP) app or browser extension to view your authentication code.',
            'placeholder' => '6-digit code',
            'invalid' => 'The code you entered is invalid.',
            'submit' => 'Verify',
        ],

        'webauthn' => [
            'use_label' => 'Use your security key',
            'use_label_including_passkeys' => 'Use your passkey or security key',
            'heading' => 'Security key',
            'heading_including_passkeys' => 'Passkey or security key',
            'waiting' => 'Waiting for input from browser interaction...',
            'failed' => 'Authentication failed.',
            'retry' => 'Retry security key',
            'retry_including_passkeys' => 'Retry passkey or security key',
            'submit' => 'Use security key',
            'submit_including_passkeys' => 'Use passkey or security key',
            'hint' => 'When you are ready, authenticate using the button below.',
            'invalid' => 'Authentication failed.',
        ],

        'password' => [
            'use_label' => 'Use your password',
            'input_label' => 'Your password',
            'submit' => 'Confirm',
            'invalid' => 'Incorrect password.',
        ],

    ],

    'masked_value' => [
        'reveal_button' => 'Reveal',
    ],

];
