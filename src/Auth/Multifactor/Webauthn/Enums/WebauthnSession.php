<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums;

enum WebauthnSession: string
{
    case AuthenticationOptions = 'webauthn-authentication-options';
    case RegistrationOptions = 'webauthn-registration-options';

    public function get(): mixed
    {
        return session()->get($this->value);
    }

    public function flash(mixed $value): void
    {
        session()->flash($this->value, $value);
    }

    public function pull(): mixed
    {
        return session()->pull($this->value);
    }

    public function put(mixed $value): void
    {
        session()->put($this->value, $value);
    }
}
