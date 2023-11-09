<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Concerns;

trait IsProfilePage
{
    /** @final This method should not be overridden in most cases. */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function shouldRegisterInnerNav(): bool
    {
        return true;
    }

    public static function innerNavGroup(): ?string
    {
        return null;
    }

    public static function innerNavSort(): int
    {
        return static::$innerNavSort ?? 99;
    }
}
