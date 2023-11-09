<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Contracts\AuthenticatorApps;

use Rawilk\ProfileFilament\Models\AuthenticatorApp;

interface DeleteAuthenticatorAppAction
{
    public function __invoke(AuthenticatorApp $authenticatorApp);
}
