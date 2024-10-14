<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Features;
use Rawilk\ProfileFilament\Filament\Actions\Sessions\RevokeSessionAction;
use Rawilk\ProfileFilament\ProfileFilamentPluginServiceProvider;
use Rawilk\ProfileFilament\Providers\AuthServiceProvider;
use Rawilk\ProfileFilament\Support\PageManager;

//arch()->preset()->php();
//arch()->preset()->security()->ignoring([
//    RevokeSessionAction::class, // Needs unserialize
//]);

//arch()->expect('Rawilk\ProfileFilament')->toUseStrictTypes();
//arch()->expect('Rawilk\ProfileFilament')->toUseStrictEquality();
//arch()->expect('Rawilk\ProfileFilament')
//    ->classes()
//    ->not->toBeFinal()->ignoring([
//        Features::class,
//        AuthServiceProvider::class,
//        ProfileFilamentPluginServiceProvider::class,
//        'Rawilk\ProfileFilament\Exceptions',
//        PageManager::class,
//    ]);

arch()->expect('Rawilk\ProfileFilament\Concerns')
    ->toBeTraits();

arch()->expect('Rawilk\ProfileFilament\Contracts')
    ->toBeInterfaces();

arch()->expect('Rawilk\ProfileFilament\Dto')
    ->classes()
    ->toExtendNothing()
    ->not->toHaveSuffix('Dto');

//arch()->expect([
//    'dd',
//    'ddd',
//    'dump',
//    'env',
//    'exit',
//    'ray',
//
//    // strict preset
//    'sleep',
//    'usleep',
//
//    // 'time()' is difficult to test; now()->timestamp should be used instead
//    'time',
//])->not->toBeUsed();
