<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Rawilk\ProfileFilament\ProfileFilamentPluginServiceProvider;
use Rawilk\ProfileFilament\Providers\AuthServiceProvider;

arch()
    ->expect('Rawilk\ProfileFilament')
    ->classes()
    ->not->toExtend(ServiceProvider::class)->ignoring([
        AuthServiceProvider::class,
        ProfileFilamentPluginServiceProvider::class,
    ]);

arch()
    ->expect('Rawilk\ProfileFilament\Providers')
    ->classes()
    ->toExtend(ServiceProvider::class)
    ->toHaveSuffix('ServiceProvider')->ignoring(['Rawilk\ProfileFilament\Providers\Concerns']);

arch()
    ->expect('Rawilk\ProfileFilament\Providers')
    ->not->toBeUsed()->ignoring(['Rawilk\ProfileFilament\Providers\Concerns']);
