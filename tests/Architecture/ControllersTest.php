<?php

declare(strict_types=1);

arch()
    ->expect([
        'Rawilk\ProfileFilament\Http\Controllers',
        'Rawilk\ProfileFilament\Auth\Webauthn\Http\Controllers',
    ])
    ->classes()
    ->toExtendNothing();

arch()
    ->expect([
        'Rawilk\ProfileFilament\Http\Controllers',
        'Rawilk\ProfileFilament\Auth\Webauthn\Http\Controllers',
    ])
    ->classes()
    ->toHaveSuffix('Controller');

arch()
    ->expect([
        'Rawilk\ProfileFilament\Http\Controllers',
        'Rawilk\ProfileFilament\Auth\Webauthn\Http\Controllers',
    ])
    ->not->toBeUsed();
