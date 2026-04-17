<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Enums;

use Illuminate\Support\Facades\Date;

enum EmailCodeSession: string
{
    case Code = 'pf-email-authentication-code';
    case ExpiresAt = 'pf-email-authentication-code-expires-at';

    public function get(): mixed
    {
        $value = session()->get($this->value);

        return match ($this) {
            self::ExpiresAt => filled($value) ? Date::createFromTimestamp($value) : null,
            default => $value,
        };
    }

    public function forget(): void
    {
        session()->forget($this->value);
    }

    public function set(mixed $value): void
    {
        session()->put($this->value, $value);
    }
}
