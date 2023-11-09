<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Exceptions\Webauthn;

use Exception;
use Webauthn\Exception\WebauthnException;

final class AttestationFailed extends Exception
{
    public static function fromWebauthnException(WebauthnException $exception): self
    {
        return new self(
            message: __('profile-filament::pages/security.mfa.webauthn.actions.register.register_fail_notification'),
            previous: $exception,
        );
    }
}
