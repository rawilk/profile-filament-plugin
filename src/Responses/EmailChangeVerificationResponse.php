<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Responses;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Rawilk\ProfileFilament\Contracts\Responses\EmailChangeVerificationResponse as Responsable;
use Rawilk\ProfileFilament\Filament\Pages\Profile\Settings;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

class EmailChangeVerificationResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->intended($this->getIntendedUrl());
    }

    protected function getIntendedUrl(): ?string
    {
        $plugin = filament(ProfileFilamentPlugin::PLUGIN_ID);
        if (! $plugin?->hasSettingsPage()) {
            return Filament::getUrl();
        }

        return $plugin->getPageUrl(Settings::class);
    }
}
