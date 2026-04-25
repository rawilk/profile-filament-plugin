<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Actions\Passkeys;

use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MarkMultiFactorDisabledAction;
use Rawilk\ProfileFilament\Contracts\Passkeys\DeletePasskeyAction as DeletePasskeyActionContract;
use Rawilk\ProfileFilament\Events\Passkeys\PasskeyDeleted;
use Rawilk\ProfileFilament\Models\WebauthnKey;

/** @deprecated */
class DeletePasskeyAction implements DeletePasskeyActionContract
{
    public function __invoke(WebauthnKey $passkey)
    {
        $user = $passkey->user;

        $passkey->delete();

        cache()->forget($user::hasPasskeysCacheKey($user));

        app(MarkMultiFactorDisabledAction::class)($user);

        PasskeyDeleted::dispatch($passkey, $user);
    }
}
