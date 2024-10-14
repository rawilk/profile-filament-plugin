<?php

declare(strict_types=1);

arch()->expect('Rawilk\ProfileFilament\Responses')
    ->classes()
    ->toHaveSuffix('Response');
