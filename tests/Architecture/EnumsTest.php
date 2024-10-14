<?php

declare(strict_types=1);

arch()->expect('Rawilk\ProfileFilament\Enums')
    ->toBeEnums()
    ->ignoring('Rawilk\ProfileFilament\Enums\Concerns');

arch()->expect('Rawilk\ProfileFilament\Enums')
    ->enums()
    ->not->toHaveSuffix('Enum');
