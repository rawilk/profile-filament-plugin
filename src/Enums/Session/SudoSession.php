<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Enums\Session;

use Illuminate\Support\Facades\Date;

enum SudoSession: string
{
    case ConfirmedAt = 'sudo.confirmed_at';

    public function forget(): void
    {
        session()->forget($this->value);
    }

    public function get(): mixed
    {
        $value = session()->get($this->value);

        return match ($this) {
            self::ConfirmedAt => filled($value) ? Date::parse($value) : null,
            default => $value,
        };
    }

    public function put(mixed $value): void
    {
        session()->put($this->value, $value);
    }
}
