<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions\Emails;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Rawilk\ProfileFilament\Models\PendingUserEmail;

class ResendPendingEmailAction extends Action
{
    use CanCustomizeProcess;
    use Concerns\RateLimitsResendPendingEmailChange;
    use Concerns\SendsEmailChangeVerification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('profile-filament::pages/settings/email/actions/resend-pending-email.label'));

        $this->link();

        $this->successNotification(
            Notification::make()
                ->success()
                ->title(__('profile-filament::pages/settings/email/actions/resend-pending-email.notifications.success.title'))
                ->body(__('profile-filament::pages/settings/email/actions/resend-pending-email.notifications.success.body'))
        );

        $this->action(function (?PendingUserEmail $record) {
            if (! $record) {
                return;
            }

            $record->loadMissing('user');

            // We'll let the user attempt this 3 times every 10 minutes.
            if (RateLimiter::tooManyAttempts(
                key: $rateLimitKey = $this->rateLimitKey($record->user),
                maxAttempts: 3,
            )) {
                $this->getRateLimitedNotification(RateLimiter::availableIn($rateLimitKey))?->send();

                $this->cancel();
            }

            RateLimiter::hit(key: $rateLimitKey, decaySeconds: 60 * 10);

            $result = $this->process(function (?PendingUserEmail $record) {
                if (! $record) {
                    return false;
                }

                // Make sure the timestamp ges updated to allow more expiration time.
                $record->touch('created_at');

                $this->sendEmailChangeVerification($record->user, $record);

                return true;
            });

            if ($result === false) {
                $this->cancel();
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'resendPendingEmail';
    }

    protected function getRateLimitedNotification(int $secondsUntilAvailable): ?Notification
    {
        return Notification::make()
            ->title(__('profile-filament::pages/settings/email/actions/resend-pending-email.messages.throttled.title'))
            ->body(
                __('profile-filament::pages/settings/email/actions/resend-pending-email.messages.throttled.body', [
                    'seconds' => $secondsUntilAvailable,
                    'minutes' => ceil($secondsUntilAvailable / 60),
                ])
            )
            ->danger();
    }
}
