<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Infolists\Concerns;

use Closure;

trait IsConfigurable
{
    protected static ?Closure $getComponentsUsing = null;

    protected static ?Closure $configureSchemaUsing = null;

    public static function configureComponents(?Closure $closure): void
    {
        static::$getComponentsUsing = $closure;
    }

    public static function configureSchema(?Closure $closure): void
    {
        static::$configureSchemaUsing = $closure;
    }
}
