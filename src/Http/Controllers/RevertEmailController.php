<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Controllers;

use Rawilk\ProfileFilament\Contracts\Responses\EmailRevertedResponse;
use Rawilk\ProfileFilament\Exceptions\PendingUserEmails\InvalidRevertLinkException;
use Rawilk\ProfileFilament\Models\OldUserEmail;

class RevertEmailController
{
    public function __invoke(string $token): EmailRevertedResponse
    {
        app(config('profile-filament.models.old_user_email'))->whereToken($token)->firstOr(['*'], function () {
            throw new InvalidRevertLinkException(__('profile-filament::pages/settings.email.invalid_revert_link'));
        })->tap(function (OldUserEmail $oldUserEmail) {
            $oldUserEmail->activate();
        });

        return app(EmailRevertedResponse::class);
    }
}
