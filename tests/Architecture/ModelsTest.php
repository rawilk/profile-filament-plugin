<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

arch()->expect('Rawilk\ProfileFilament\Models')
    ->classes()
    ->toExtend(Model::class)
    ->ignoring('Rawilk\ProfileFilament\Models\Scopes');

arch()->expect('Rawilk\ProfileFilament\Models')
    ->classes()
    ->not->toHaveSuffix('Model');

//arch()->expect('Rawilk\ProfileFilament')
//    ->classes()
//    ->not->toExtend(Model::class)
//    ->ignoring([
//        'Rawilk\ProfileFilament\Models',
//    ]);
