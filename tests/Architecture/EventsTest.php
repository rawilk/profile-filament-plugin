<?php

declare(strict_types=1);

use Rawilk\ProfileFilament\Events\ProfileFilamentEvent;

arch()->expect('Rawilk\ProfileFilament\Events')
    ->classes()
    ->not->toHaveSuffix('Event')->ignoring([
        ProfileFilamentEvent::class,
    ]);

arch()->expect('Rawilk\ProfileFilament\Events')
    ->classes()
    ->toExtend(ProfileFilamentEvent::class)->ignoring([
        ProfileFilamentEvent::class,
    ]);
