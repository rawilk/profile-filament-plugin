<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Notifications\Emails;

use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NoticeOfEmailChangeRequest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The callback that should be used to build the mail message.
     *
     * @var (Closure(mixed, string): MailMessage|Mailable)|null
     */
    public static Mailable|Closure|null $toMailCallback = null;

    public function __construct(
        protected readonly string $newEmail,
        protected readonly string $blockVerificationUrl,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->blockVerificationUrl, $this->newEmail);
        }

        return (new MailMessage)
            ->subject(__('profile-filament::mail.notice-of-email-change-request.subject'))
            ->lines([
                __('profile-filament::mail.notice-of-email-change-request.lines.0', ['email' => $this->newEmail]),
                __('profile-filament::mail.notice-of-email-change-request.lines.1', ['email' => $this->newEmail]),
                __('profile-filament::mail.notice-of-email-change-request.lines.2', ['email' => $this->newEmail]),
                __('profile-filament::mail.notice-of-email-change-request.lines.3', ['email' => $this->newEmail]),
            ])
            ->action(__('profile-filament::mail.notice-of-email-change-request.action'), $this->blockVerificationUrl);
    }
}
