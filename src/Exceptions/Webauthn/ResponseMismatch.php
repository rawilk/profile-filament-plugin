<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Exceptions\Webauthn;

use RuntimeException;

class ResponseMismatch extends RuntimeException
{
    public static function attestation(): static
    {
        return new static('Not an authenticator attestation response');
    }

    public static function assertion(): static
    {
        return new static('Not an authenticator assertion response.');
    }
}
