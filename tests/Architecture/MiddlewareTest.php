<?php

declare(strict_types=1);

arch()->expect('Rawilk\ProfileFilament\Http\Middleware')
    ->classes()
    ->toExtendNothing();

arch()->expect('Rawilk\ProfileFilament\Http\Middleware')
    ->classes()
    ->not->toHaveSuffix('Middleware');

arch()->expect('Rawilk\ProfileFilament\Http\Middleware')
    ->classes()
    ->toHaveMethod('handle');
