<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Responses;

use Filament\Auth\Http\Responses\LoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Rawilk\ProfileFilament\Contracts\Responses\MultiFactorChallengeResponse as Responsable;

class MultiFactorChallengeResponse extends LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->to(
            filament()->getCurrentPanel()->route('auth.multi-factor-challenge'),
        );
    }
}
