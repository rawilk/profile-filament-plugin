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
        return redirect()
            ->intended($this->getIntendedUrl())
            ->with('success', __('profile-filament::pages/settings.email.email_reverted'));
    }

    protected function getIntendedUrl(): ?string
    {
        return auth()->check()
            ? (Filament::getHomeUrl() ?? Filament::getUrl())
            : Filament::getLoginUrl();
    }
}
