<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts\Passkeys;

use Rawilk\ProfileFilament\Models\WebauthnKey;

interface DeletePasskeyAction
{
    public function __invoke(WebauthnKey $passkey);
}
