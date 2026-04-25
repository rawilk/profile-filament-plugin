<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Exceptions;

use Exception;

class InvalidSecurityKeyOptions extends Exception
{
    public static function invalidJson(): self
    {
        return new self('The given security key options should be formatted as JSON. Please check the format and try again.');
    }
}
