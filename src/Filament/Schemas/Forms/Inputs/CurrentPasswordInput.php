<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs;

use Filament\Facades\Filament;
use Rawilk\FilamentPasswordInput\Password;

class CurrentPasswordInput extends Password
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->autocomplete('current-password');

        $this->required();

        $this->currentPassword(guard: Filament::getAuthGuard());
    }
}
