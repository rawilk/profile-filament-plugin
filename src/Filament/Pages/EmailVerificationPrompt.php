<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt as BasePage;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\RateLimiter;
use LogicException;
use Rawilk\ProfileFilament\Facades\ProfileFilament;

class EmailVerificationPrompt extends BasePage
{
    public function resendNotificationAction(): Action
    {
        return parent::resendNotificationAction()
            ->button()
            ->label(__('profile-filament::pages/email-verification-prompt.action'))
            ->size(Size::Large)
            ->extraAttributes([
                'class' => 'w-full',
            ])
            ->action(function (): void {
                $rateLimitingKey = 'filament-resend-email-verification:' . Filament::auth()->id();

                if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 2)) {
                    $this->getRateLimitedNotification(new TooManyRequestsException(
                        static::class,
                        'resendNotification',
                        request()->ip(),
                        RateLimiter::availableIn($rateLimitingKey),
                    ))?->send();

                    return;
                }

                RateLimiter::hit($rateLimitingKey);

                $this->sendEmailVerificationNotification($this->getVerifiable());

                Notification::make()
                    ->title(__('filament-panels::auth/pages/email-verification/email-verification-prompt.notifications.notification_resent.title'))
                    ->success()
                    ->send();
            });
    }

    public function content(Schema $schema): Schema
    {
        $email = Filament::auth()->user()->getEmailForVerification();

        return $schema
            ->components([
                Text::make(str(__('profile-filament::pages/email-verification-prompt.messages.0', [
                    'email' => $email,
                ]))->inlineMarkdown()->toHtmlString()),

                Text::make(str(__('profile-filament::pages/email-verification-prompt.messages.1', [
                    'email' => $email,
                ]))->inlineMarkdown()->toHtmlString()),

                Text::make(str(__('profile-filament::pages/email-verification-prompt.messages.2', [
                    'email' => $email,
                ]))->inlineMarkdown()->toHtmlString()),

                $this->resendNotificationAction,
            ]);
    }

    protected function sendEmailVerificationNotification(MustVerifyEmail $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new LogicException("Model [{$userClass}] does not have a [notify()] method.");
        }

        $notification = app(VerifyEmail::class);
        $notification->url = ProfileFilament::getEmailVerificationUrl($user);

        $user->notify($notification);
    }
}
