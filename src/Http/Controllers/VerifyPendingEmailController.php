<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Controllers;

use Rawilk\ProfileFilament\Contracts\Responses\PendingEmailVerifiedResponse;
use Rawilk\ProfileFilament\Exceptions\PendingUserEmails\InvalidVerificationLinkException;
use Rawilk\ProfileFilament\Models\PendingUserEmail;

class VerifyPendingEmailController
{
    public function __invoke(string $token): PendingEmailVerifiedResponse
    {
        $user = app(config('profile-filament.models.pending_user_email'))->whereToken($token)->firstOr(['*'], function () {
            throw new InvalidVerificationLinkException(__('profile-filament::pages/settings.email.invalid_verification_link'));
        })->tap(function (PendingUserEmail $pendingUserEmail) {
            $pendingUserEmail->activate();
        })->user;

        if (config('profile-filament.pending_email_changes.login_after_verification')) {
            filament()->auth()->login($user, config('profile-filament.pending_email_changes.login_remember', true));
        }

        return app(PendingEmailVerifiedResponse::class);
    }
}
