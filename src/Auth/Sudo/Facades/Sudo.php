<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Facades;

use Illuminate\Support\Facades\Facade;
use Rawilk\ProfileFilament\Auth\Sudo\Services\Sudo as SudoService;

/**
 * @method static void deactivate()
 * @method static void activate()
 * @method static void extend()
 * @method static bool isValid()
 *
 * @see SudoService
 */
class Sudo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SudoService::class;
    }
}
