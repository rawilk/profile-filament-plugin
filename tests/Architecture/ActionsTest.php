<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Actions\Auth\PrepareUserSession;

arch()->expect('Rawilk\ProfileFilament\Actions')
    ->toBeClasses()
    ->toHaveSuffix('Action')
    ->ignoring([
        PrepareUserSession::class,
    ]);
