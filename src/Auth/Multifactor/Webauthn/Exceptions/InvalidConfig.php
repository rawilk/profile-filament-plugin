<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Exceptions;

use Exception;

class InvalidConfig extends Exception
{
    public static function invalidAuthenticatorAttachment(string $value, array $supportedValues): self
    {
        return new self('Invalid webauthn authenticator attachment `' . $value . '`. Must be one of: ' . implode(', ', $supportedValues));
    }

    public static function invalidUserVerification(string $value, array $supportedValues): self
    {
        return new self('Invalid webauthn user verification `' . $value . '`. Must be one of: ' . implode(', ', $supportedValues));
    }

    public static function invalidResidentKeyRequirement(string $value, array $supportedValues): self
    {
        return new self('Invalid webauthn resident key requirement `' . $value . '`. Must be one of: ' . implode(', ', $supportedValues));
    }
}
