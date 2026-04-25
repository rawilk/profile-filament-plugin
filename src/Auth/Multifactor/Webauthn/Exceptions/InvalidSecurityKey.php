<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Exceptions;

use Exception;
use Throwable;

class InvalidSecurityKey extends Exception
{
    public static function invalidJson(): self
    {
        return new self('The given security key should be formatted as json. Please check the format and try again.');
    }

    public static function invalidPublicKeyCredential(): self
    {
        return new self('The given security key is not a valid public key credential. Please check the format and try again.');
    }

    public static function invalidAuthenticatorAttestationResponse(Throwable $exception): self
    {
        return new self(
            'The given security key could not be validated. Please check the format and try again.',
            previous: $exception,
        );
    }
}
