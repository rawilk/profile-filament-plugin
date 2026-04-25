<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions;

use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MarkMultiFactorDisabledAction;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyDeleted;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class DeleteSecurityKeyAction
{
    public function __invoke(WebauthnKey $webauthnKey): void
    {
        /** @var \Illuminate\Database\Eloquent\Model&\Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts\HasWebauthn $user */
        $user = $webauthnKey->user;

        $user->securityKeys()->whereKey($webauthnKey->getKey())->delete();

        app(MarkMultiFactorDisabledAction::class)($user);

        WebauthnKeyDeleted::dispatch($webauthnKey, $user);
    }
}
