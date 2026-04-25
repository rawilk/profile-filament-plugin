<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Enums;

use Illuminate\Support\Facades\Crypt;

enum MfaSession: string
{
    case ConfirmedAt = 'mfa.confirmed_at';
    case UserBeingAuthenticated = 'mfa.user';
    case Remember = 'mfa.remember';
    // When the user is logging in, this is when they've submitted the login form with their email/password.
    case PasswordConfirmedAt = 'mfa.password_confirmed_at';

    public function get(): mixed
    {
        $value = session()->get($this->value);

        return match ($this) {
            self::Remember => (bool) $value,
            self::UserBeingAuthenticated => filled($value) ? Crypt::decrypt($value) : null,
            default => $value,
        };
    }

    public function isTrue(): bool
    {
        return $this->get() === true;
    }

    public function has(): bool
    {
        return session()->has($this->value);
    }

    public function set(mixed $value): void
    {
        $value = match ($this) {
            self::UserBeingAuthenticated => filled($value) ? Crypt::encrypt((string) $value) : null,
            default => $value,
        };

        session()->put($this->value, $value);
    }

    public function forget(): void
    {
        session()->forget($this->value);
    }
}
