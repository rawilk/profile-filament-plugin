<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Profile;

use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Rawilk\ProfileFilament\Filament\Schemas\Infolists\ProfileInfolist;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;

/**
 * This component is meant to be extended by your own component,
 * so it's very basic on purpose.
 */
class ProfileInfo extends ProfileComponent
{
    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->infolist }}

            <x-filament-actions::modals />
        </div>
        HTML;
    }

    public function infoList(Schema $schema): Schema
    {
        return ProfileInfolist::configure(
            schema: $schema,
            record: Filament::auth()->user(),
        );
    }
}
