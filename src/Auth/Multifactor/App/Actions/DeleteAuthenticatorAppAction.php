<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Actions;

use Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\DeleteAuthenticatorAppAction as DeleteAuthenticatorAppActionContract;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Events\AuthenticatorAppWasDeleted;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MarkMultiFactorDisabledAction;
use Rawilk\ProfileFilament\Models\AuthenticatorApp;

class DeleteAuthenticatorAppAction implements DeleteAuthenticatorAppActionContract
{
    public function __invoke(AuthenticatorApp $authenticatorApp): void
    {
        /** @var \Illuminate\Database\Eloquent\Model&\Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\HasAppAuthentication $user */
        $user = $authenticatorApp->user;

        $user->authenticatorApps()->whereKey($authenticatorApp->getKey())->delete();

        app(MarkMultiFactorDisabledAction::class)($user);

        AuthenticatorAppWasDeleted::dispatch($user, $authenticatorApp);
    }
}
