<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts;

use Rawilk\ProfileFilament\Models\AuthenticatorApp;

interface DeleteAuthenticatorAppAction
{
    public function __invoke(AuthenticatorApp $authenticatorApp);
}
