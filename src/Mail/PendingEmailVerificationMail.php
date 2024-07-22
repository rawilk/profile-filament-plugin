<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Rawilk\ProfileFilament\Models\PendingUserEmail;

class PendingEmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public PendingUserEmail $pendingUserEmail,
        public ?string $panelId = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('profile-filament::mail.pending_email_verification.subject'),
        );
    }

    public function content(): Content
    {
        if ($this->panelId) {
            filament()->setCurrentPanel(filament()->getPanel($this->panelId));
        }

        return new Content(
            markdown: 'profile-filament::mail.pending-email-verification',
            with: [
                'url' => $this->pendingUserEmail->verification_url,
                'email' => $this->pendingUserEmail->email,
            ],
        );
    }
}
