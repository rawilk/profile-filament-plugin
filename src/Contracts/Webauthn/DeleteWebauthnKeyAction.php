<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts\Webauthn;

use Rawilk\ProfileFilament\Models\WebauthnKey;

interface DeleteWebauthnKeyAction
{
    public function __invoke(WebauthnKey $webauthnKey);
}
