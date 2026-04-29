<?php

declare(strict_types=1);

arch()
    ->expect([
        'Rawilk\ProfileFilament\Http\Middleware',
        'Rawilk\ProfileFilament\Auth\*\Http\Middleware',
    ])
    ->classes()
    ->toExtendNothing();

arch()
    ->expect([
        'Rawilk\ProfileFilament\Http\Middleware',
        'Rawilk\ProfileFilament\Auth\*\Http\Middleware',
    ])
    ->classes()
    ->not->toHaveSuffix('Middleware');

arch()
    ->expect([
        'Rawilk\ProfileFilament\Http\Middleware',
        'Rawilk\ProfileFilament\Auth\*\Http\Middleware',
    ])
    ->classes()
    ->toHaveMethod('handle');
