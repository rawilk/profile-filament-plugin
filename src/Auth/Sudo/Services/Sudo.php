<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Services;

use DateInterval;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Traits\Macroable;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;

class Sudo
{
    use Macroable;

    public function __construct(protected DateInterval $expiration)
    {
    }

    public function deactivate(): void
    {
        SudoSession::ConfirmedAt->forget();
    }

    public function activate(): void
    {
        SudoSession::ConfirmedAt->put(Date::now()->unix());
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
        /** @var null|\Carbon\CarbonInterface $lastConfirmed */
        $lastConfirmed = SudoSession::ConfirmedAt->get();

        return $lastConfirmed?->add($this->expiration)->isFuture() ?? false;
    }
}
