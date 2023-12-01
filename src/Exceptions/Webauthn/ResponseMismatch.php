<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Exceptions\Webauthn;

use RuntimeException;

final class ResponseMismatch extends RuntimeException
{
    public static function attestation(): self
    {
        return new self('Not an authenticator attestation response');
    }

    public static function assertion(): self
    {
        return new self('Not an authenticator assertion response.');
    }
}
