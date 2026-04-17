<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Notifications\Emails;

use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailChange extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The callback that should be used to build the mail message.
     *
     * @var (Closure(mixed, string): MailMessage|Mailable)|null
     */
    public static Mailable|Closure|null $toMailCallback = null;

    public string $url;

    public function __construct(protected readonly string $newEmail)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl, $this->newEmail);
        }

        $linkExpires = $this->getLinkExpiration();

        return (new MailMessage)
            ->subject(__('profile-filament::mail.verify-email-change.subject'))
            ->line(__('profile-filament::mail.verify-email-change.lines.0', ['email' => $this->newEmail, 'expire' => $linkExpires]))
            ->action(__('profile-filament::mail.verify-email-change.action'), $verificationUrl)
            ->lines([
                __('profile-filament::mail.verify-email-change.lines.1', ['email' => $this->newEmail, 'expire' => $linkExpires]),
                __('profile-filament::mail.verify-email-change.lines.2', ['email' => $this->newEmail, 'expire' => $linkExpires]),
            ]);
    }

    protected function verificationUrl($notifiable): string
    {
        return $this->url;
    }

    protected function getLinkExpiration(): string
    {
        return now()->diffForHumans(
            other: now()->addMinutes(config('auth.verification.expire', 60)),
            syntax: true,
            parts: 2,
        );
    }
}
