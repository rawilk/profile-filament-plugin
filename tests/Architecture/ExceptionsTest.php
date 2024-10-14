<?php

declare(strict_types=1);

arch()->expect('Rawilk\ProfileFilament\Exceptions')
    ->classes()
    ->toImplement('Throwable');
