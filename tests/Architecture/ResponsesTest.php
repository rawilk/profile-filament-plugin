<?php

declare(strict_types=1);

use Illuminate\Contracts\Support\Responsable;

arch()
    ->expect('Rawilk\ProfileFilament\Responses')
    ->classes()
    ->toHaveSuffix('Response')
    ->toImplement(Responsable::class);
