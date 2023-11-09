<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\Webauthn;

use Rawilk\ProfileFilament\Contracts\TwoFactor\MarkTwoFactorDisabledAction;
use Rawilk\ProfileFilament\Contracts\Webauthn\DeleteWebauthnKeyAction as DeleteWebauthnKeyActionContract;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyDeleted;
use Rawilk\ProfileFilament\Models\WebauthnKey;

class DeleteWebauthnKeyAction implements DeleteWebauthnKeyActionContract
{
    public function __invoke(WebauthnKey $webauthnKey)
    {
        $webauthnKey->delete();

        app(MarkTwoFactorDisabledAction::class)($webauthnKey->user);

        WebauthnKeyDeleted::dispatch($webauthnKey, $webauthnKey->user);
    }
}
