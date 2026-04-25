<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Actions;

use Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\DeleteAuthenticatorAppAction as DeleteAuthenticatorAppActionContract;
use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorDisabledAction;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppRemoved;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class DeleteAuthenticatorAppAction implements DeleteAuthenticatorAppActionContract
{
    public function __invoke(AuthenticatorApp $authenticatorApp): void
    {
        /** @var \Illuminate\Database\Eloquent\Model&\Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\HasAppAuthentication $user */
        $user = $authenticatorApp->user;

        $user->authenticatorApps()->whereKey($authenticatorApp->getKey())->delete();

        app(MarkTwoFactorDisabledAction::class)($user);

        TwoFactorAppRemoved::dispatch($user, $authenticatorApp);
    }
}
