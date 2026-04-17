<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Controllers;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Rawilk\ProfileFilament\Contracts\Responses\BlockEmailVerificationResponse;
use Rawilk\ProfileFilament\Http\Requests\BlockEmailChangeRequest;

class BlockEmailChangeVerificationController
{
    public function __invoke(BlockEmailChangeRequest $request): BlockEmailVerificationResponse
    {
        $isSuccessful = $request->fulfill();

        if ($isSuccessful) {
            Notification::make()
                ->title(__('filament-panels::auth/http/controllers/block-email-change-verification-controller.notifications.blocked.title'))
                ->body(__('filament-panels::auth/http/controllers/block-email-change-verification-controller.notifications.blocked.body', [
                    'email' => Crypt::decryptString($request->route('email')),
                ]))
                ->success()
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title(__('filament-panels::auth/http/controllers/block-email-change-verification-controller.notifications.failed.title'))
                ->body(__('filament-panels::auth/http/controllers/block-email-change-verification-controller.notifications.failed.body', [
                    'email' => Crypt::decryptString($request->route('email')),
                ]))
                ->danger()
                ->persistent()
                ->send();
        }

        return app(BlockEmailVerificationResponse::class);
    }
}
