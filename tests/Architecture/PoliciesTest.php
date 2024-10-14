<?php

declare(strict_types=1);

arch()->expect('Rawilk\ProfileFilament\Policies')
    ->classes()
    ->toExtendNothing();

arch()->expect('Rawilk\ProfileFilament\Policies')
    ->classes()
    ->toHaveSuffix('Policy');
