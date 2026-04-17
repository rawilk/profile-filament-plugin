<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Http\Controllers;

use Filament\Auth\Http\Responses\Contracts\EmailVerificationResponse;
use Rawilk\ProfileFilament\Http\Requests\EmailVerificationRequest;

class EmailVerificationController
{
    public function __invoke(EmailVerificationRequest $request): EmailVerificationResponse
    {
        $request->fulfill();

        return app(EmailVerificationResponse::class);
    }
}
