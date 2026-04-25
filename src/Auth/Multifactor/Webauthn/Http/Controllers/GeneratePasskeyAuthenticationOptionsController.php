<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Http\Controllers;

use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\GenerateSecurityKeyAuthenticationOptionsAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Support\Config;

class GeneratePasskeyAuthenticationOptionsController
{
    public function __invoke(): string
    {
        $action = Config::getWebauthnAction(
            'generate_security_key_authentication_options',
            GenerateSecurityKeyAuthenticationOptionsAction::class,
        );

        $options = $action(isPasskey: true);

        WebauthnSession::AuthenticationOptions->put($options);

        return $options;
    }
}
