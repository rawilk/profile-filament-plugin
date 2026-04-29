<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Facade;

arch()
    ->expect([
        'Rawilk\ProfileFilament\Facades',
        'Rawilk\ProfileFilament\Auth\*\Facades',
    ])
    ->toBeClasses()
    ->toExtend(Facade::class)
    ->not->toHaveSuffix('Facade');
