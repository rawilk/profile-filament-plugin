<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Services;

use DateInterval;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Traits\Macroable;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;

class Sudo
{
    use Macroable;

    public function __construct(protected DateInterval $expiration) {}

    public function deactivate(): void
    {
        session()->forget(SudoSession::ConfirmedAt->value);
    }

    public function activate(): void
    {
        session()->put(SudoSession::ConfirmedAt->value, Date::now()->unix());
    }

    /**
     * Alias of "activate". Mostly here for semantics.
     */
    public function extend(): void
    {
        $this->activate();
    }

    public function isActive(): bool
    {
        $lastConfirmed = Date::parse(
            session()->get(SudoSession::ConfirmedAt->value, 0)
        );

        return $lastConfirmed->add($this->expiration)->isFuture();
    }
}
