<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Emails\Concerns;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Models\PendingUserEmail;
use Rawilk\ProfileFilament\Notifications\Emails\VerifyEmailChange;

trait SendsEmailChangeVerification
{
    protected function getEmailChangeVerificationRecipientWithNewEmail(Model $record, VerifyEmailChange $notification, string $newEmail): string|array
    {
        if (! method_exists($record, 'routeNotificationForMail')) {
            return $newEmail;
        }

        $recipient = $record->routeNotificationForMail($notification);
        $currentEmail = $record->getAttributeValue('email');

        if (
            (! is_array($recipient))
            || (! array_key_exists($currentEmail ?? '', $recipient))
        ) {
            return $newEmail;
        }

        return [$newEmail => $recipient[$currentEmail]];
    }

    protected function sendEmailChangeVerification(Model $record, PendingUserEmail $pendingUserEmail): void
    {
        $notification = app(VerifyEmailChange::class, [
            'newEmail' => $pendingUserEmail->email,
        ]);
        $notification->url = ProfileFilament::getVerifyEmailChangeUrl($record, $pendingUserEmail->email, [
            'token' => $pendingUserEmail->token,
        ]);

        $newEmailRecipient = $this->getEmailChangeVerificationRecipientWithNewEmail($record, $notification, $pendingUserEmail->email);

        if ($record instanceof HasLocalePreference) {
            $notification->locale($record->preferredLocale());
        }

        Notification::route('mail', $newEmailRecipient)->notify($notification);
    }
}
