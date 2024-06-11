<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Exceptions\PendingUserEmails;

use Filament\Facades\Filament;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class InvalidVerificationLinkException extends AuthenticationException
{
    public function __construct(
        string $message = 'Unauthenticated.',
        array $guards = [],
        ?string $redirectTo = null,
    ) {
        parent::__construct($message, $guards, $redirectTo);
    }

    public function render()
    {
        session()->flash('error', $this->message);
    }

    public function redirectTo(Request $request): string
    {
        if ($this->redirectTo) {
            return $this->redirectTo;
        }

        if (Filament::auth()->check()) {
            return Filament::getHomeUrl();
        }

        $panel = Filament::getCurrentPanel() ?? Filament::getDefaultPanel();
        $panelId = $panel->getId();

        return route("filament.{$panelId}.auth.login");
    }
}
