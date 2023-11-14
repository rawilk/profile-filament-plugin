<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Responses;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Rawilk\ProfileFilament\Contracts\Responses\EmailRevertedResponse as EmailRevertedResponseContract;

class EmailRevertedResponse implements EmailRevertedResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        session()->flash('success', __('profile-filament::pages/settings.email.email_reverted'));

        return redirect()->intended(Filament::getHomeUrl());
    }
}
