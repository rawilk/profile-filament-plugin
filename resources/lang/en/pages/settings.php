<?php

declare(strict_types=1);

return [
    'title' => 'Account',

    'account_security_link' => 'Looking to manage account security settings? You can find them in the [Password and authentication](:url) page.',

    'email' => [
        'invalid_verification_link' => 'This verification link has already been consumed or is expired. Please request a new one to verify your email address.',
        'email_already_taken' => 'The email address from your link has already been taken.',
        'email_verified' => 'Your new email address has been verified and can now be used to sign-in.',
        'invalid_revert_link' => 'This link has already been consumed or is expired. Please contact our support for further assistance.',
        'email_reverted' => 'Your email address has been reverted back to what it was and can now be used to sign-in with.',

        'heading' => 'Email address',
        'change_pending_badge' => 'Change pending',
        'email_description' => 'This email will be used for sign-in, account-related notifications and can also be used for password resets.',

        'pending_heading' => 'Confirm your email',
        'pending_description' => "We just need you to check your email **:email** and click the verification link we sent you to verify it's you and complete the update. Your change will not take effect until you've confirmed your new email.",

        'actions' => [

            'edit' => [
                'trigger' => 'Change email',
                'modal_title' => 'Edit email address',
                'email_label' => 'New email address',
                'email_placeholder' => 'example@:host',
                'email_help' => 'We will send an email to this address to verify you have access to it. Your changes will not take effect until you verify the new email address.',
                'success_title' => 'Success!',
                'success_body' => 'Your email address has been updated.',
                'success_body_pending' => 'Check your new email address for a verification link.',
            ],

            'resend' => [
                'trigger' => 'Resend email',
                'success_title' => 'Success!',
                'success_body' => 'A new verification link has been sent to your new email address.',

                'throttled' => [
                    'title' => 'Too many requests',
                    'body' => 'Please try again in :minutes minutes.',
                ],
            ],

            'cancel' => [
                'trigger' => 'Undo email change',
            ],

        ],
    ],
];
