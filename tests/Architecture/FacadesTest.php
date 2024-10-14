<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Facade;

arch()->expect('Rawilk\ProfileFilament\Facades')
    ->toBeClasses()
    ->toExtend(Facade::class)
    ->not->toHaveSuffix('Facade');
