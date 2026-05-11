<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Schemas\Infolists\Components;

use Filament\Infolists\Components\TextEntry;

class EmailTextEntry extends TextEntry
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('profile-filament::pages/settings/email/component.infolist.email.label'));

        $this->helperText(__('profile-filament::pages/settings/email/component.infolist.email.help'));
    }
}
