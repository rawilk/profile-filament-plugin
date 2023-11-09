<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Exceptions\Webauthn;

use Exception;
use Webauthn\Exception\WebauthnException;

final class AssertionFailed extends Exception
{
    public static function keyNotFound($modelId): self
    {
        return new self(__("Webauthn key with id \"{$modelId}\" was not found."));
    }

    public static function passkeyRequired(): self
    {
        return new self(__('profile-filament::pages/mfa.webauthn.assert.passkey_required'));
    }

    public static function fromWebauthnException(WebauthnException $exception): self
    {
        return new self(
            message: __('profile-filament::pages/mfa.webauthn.assert.failure'),
            previous: $exception,
        );
    }
}
