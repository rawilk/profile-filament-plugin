<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Mail;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Models\OldUserEmail;

class PendingEmailVerifiedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $newEmail,
        public OldUserEmail $oldUserEmail,
        public ?string $panelId = null,
        public ?string $ip = null,
        public ?DateTimeInterface $date = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('profile-filament::mail.email_updated.subject'),
        );
    }

    public function content(): Content
    {
        if ($this->panelId) {
            filament()->setCurrentPanel(filament()->getPanel($this->panelId));
        }

        return new Content(
            markdown: 'profile-filament::mail.email-updated',
            with: [
                'maskedEmail' => $this->anonymizeEmail($this->newEmail),
                'url' => $this->oldUserEmail->revert_url,
                'linkExpirationDays' => now()->diffInDays(now()->add(config('profile-filament.pending_email_changes.revert_expiration'))),
                'requestDetails' => $this->requestDetailsMarkup(),
            ],
        );
    }

    protected function requestDetailsMarkup(): ?string
    {
        if (! $this->ip && ! $this->date) {
            return null;
        }

        $markup = __('profile-filament::mail.request_details.heading');

        if ($this->ip) {
            $markup .= '<br>' . __('profile-filament::mail.request_details.ip', ['ip' => $this->ip]);
        }

        if ($this->date) {
            $markup .= '<br>' . __('profile-filament::mail.request_details.date', ['date' => $this->date->format('D, j M Y g:i A (T O)')]);
        }

        return $markup;
    }

    protected function anonymizeEmail(string $email): string
    {
        [$handle, $host] = explode('@', $email);
        [$hostName, $tld] = explode('.', $host, 2);

        return Str::mask($handle, '*', 2) . '@' . Str::mask($hostName, '*', 2) . '.' . $tld;
    }
}
