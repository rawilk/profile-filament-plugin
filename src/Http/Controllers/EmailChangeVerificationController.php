<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Controllers;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Rawilk\ProfileFilament\Contracts\Responses\EmailChangeVerificationResponse;
use Rawilk\ProfileFilament\Http\Requests\EmailChangeVerificationRequest;

class EmailChangeVerificationController
{
    public function __invoke(EmailChangeVerificationRequest $request): EmailChangeVerificationResponse
    {
        $request->fulfill();

        Notification::make()
            ->title(__('filament-panels::auth/http/controllers/email-change-verification-controller.notifications.verified.title'))
            ->body(__('filament-panels::auth/http/controllers/email-change-verification-controller.notifications.verified.body', [
                'email' => Crypt::decryptString($request->route('email')),
            ]))
            ->success()
            ->send();

        return app(EmailChangeVerificationResponse::class);
    }
}
