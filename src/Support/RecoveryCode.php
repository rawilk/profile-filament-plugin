<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Support;

use Illuminate\Support\Str;

class RecoveryCode
{
    /**
     * The callback that is responsible for generating a new recovery code.
     *
     * @var callable|null
     */
    protected static $generateCodesUsingCallback;

    /**
     * Register a callback that is responsible for generating a new recovery code.
     */
    public static function generateCodesUsing(callable $callback): void
    {
        static::$generateCodesUsingCallback = $callback;
    }

    public static function generate(): string
    {
        if (is_callable(static::$generateCodesUsingCallback)) {
            return call_user_func(static::$generateCodesUsingCallback);
        }

        return Str::random(10) . '-' . Str::random(10);
    }
}
