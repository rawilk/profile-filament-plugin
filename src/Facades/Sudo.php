<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void deactivate()
 * @method static void activate()
 * @method static void extend()
 * @method static bool isActive()
 *
 * @see \Rawilk\ProfileFilament\Services\Sudo
 */
class Sudo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Rawilk\ProfileFilament\Services\Sudo::class;
    }
}
