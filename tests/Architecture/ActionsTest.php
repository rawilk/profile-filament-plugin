<?php

declare(strict_types=1);

arch()
    ->expect([
        'Rawilk\ProfileFilament\Actions',
        'Rawilk\ProfileFilament\Auth\Multifactor\Actions',
        'Rawilk\ProfileFilament\Auth\Multifactor\*\Actions',
        'Rawilk\ProfileFilament\Auth\Sudo\Actions',
    ])
    ->toBeClasses()->ignoring([
        'Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns',
    ])
    ->toHaveSuffix('Action')->ignoring([
        'Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns',
    ]);
