<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Mail;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
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
    ) {
    }

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
                'linkExpires' => $this->getLinkExpiration(),
                'requestDetails' => $this->requestDetailsMarkup(),
            ],
        );
    }

    protected function getLinkExpiration(): string
    {
        return now()->diffForHumans(
            other: now()->add(config('profile-filament.pending_email_changes.revert_expiration')),
            syntax: true,
            parts: 2,
        );
    }

    protected function requestDetailsMarkup(): ?Htmlable
    {
        if (! $this->ip && ! $this->date) {
            return null;
        }

        return str(__('profile-filament::mail.request_details.heading'))
            ->when(
                filled($this->ip),
                fn (Stringable $str) => $str->append('<br>', __('profile-filament::mail.request_details.ip', ['ip' => $this->ip])),
            )
            ->when(
                $this->date !== null,
                fn (Stringable $str) => $str->append('<br>', __('profile-filament::mail.request_details.date', ['date' => $this->date->format('D, j M Y g:i A (T O)')])),
            )
            ->inlineMarkdown()
            ->toHtmlString();
    }

    protected function anonymizeEmail(string $email): string
    {
        return Str::of($email)
            ->before('@')
            // Mask the handle:
            // - >= 5 chars: reveal first & last 2, mask the middle
            // - 2-4 chars: reveal first character, mask the rest
            // - 1 char: mask the entire string
            ->when(
                fn (Stringable $str): bool => $str->length() >= 5,
                fn (Stringable $str): Stringable => $str->mask('*', 2, -2),
                fn (Stringable $str): Stringable => $str->when(
                    fn (Stringable $str): bool => $str->length() >= 2,
                    fn (Stringable $str): Stringable => $str->mask('*', 1),
                    fn (Stringable $str): Stringable => $str->mask('*', 0),
                ),
            )
            ->append('@')
            // Mask the host name
            ->append(
                Str::of(Str::after($email, '@'))
                    ->beforeLast('.')
                    ->mask('*', 2)
            )
            ->append('.')
            ->append(Str::afterLast('.', $email))
            ->value();
    }
}
