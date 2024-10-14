<?php

declare(strict_types=1);

arch()->expect('Rawilk\ProfileFilament\Http\Controllers')
    ->classes()
    ->toExtendNothing();

arch()->expect('Rawilk\ProfileFilament\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');
