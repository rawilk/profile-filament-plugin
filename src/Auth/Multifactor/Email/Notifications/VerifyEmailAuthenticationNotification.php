<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;

class VerifyEmailAuthenticationNotification extends Notification
{
    public function __construct(
        public readonly string $code,
        public readonly int $codeExpiryMinutes,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $lines = array_map(
            fn ($line) => str(
                __($line, [
                    'code' => $this->code,
                    'minutes' => $this->codeExpiryMinutes,
                ]),
            )
                ->inlineMarkdown()
                ->toHtmlString(),
            Arr::wrap(__('profile-filament::auth/multi-factor/email/notifications/verify-email-authentication.lines'))
        );

        return (new MailMessage)
            ->subject(__('profile-filament::auth/multi-factor/email/notifications/verify-email-authentication.subject'))
            ->greeting(__('profile-filament::auth/multi-factor/email/notifications/verify-email-authentication.greeting'))
            ->lines($lines);
    }
}
