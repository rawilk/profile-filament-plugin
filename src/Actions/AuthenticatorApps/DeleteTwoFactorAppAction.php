<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\AuthenticatorApps;

use Rawilk\ProfileFilament\Contracts\AuthenticatorApps\DeleteAuthenticatorAppAction;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorDisabledAction;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppRemoved;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class DeleteTwoFactorAppAction implements DeleteAuthenticatorAppAction
{
    public function __invoke(AuthenticatorApp $authenticatorApp): void
    {
        $authenticatorApp->delete();

        app(MarkTwoFactorDisabledAction::class)($authenticatorApp->user);

        TwoFactorAppRemoved::dispatch($authenticatorApp->user, $authenticatorApp);
    }
}
