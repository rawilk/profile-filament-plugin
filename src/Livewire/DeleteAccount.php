<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Schema;
use Rawilk\ProfileFilament\Filament\Actions\Users\DeleteAccountAction;
use Rawilk\ProfileFilament\Filament\Schemas\Infolists\DeleteAccountInfolist;

class DeleteAccount extends ProfileComponent implements HasInfolists
{
    use InteractsWithInfolists;

    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->infolist }}

            <x-filament-actions::modals />
        </div>
        HTML;
    }

    public function infolist(Schema $schema): Schema
    {
        return DeleteAccountInfolist::configure(
            schema: $schema,
            record: Filament::auth()->user(),
            deleteAction: $this->deleteAccountAction(),
        );
    }

    public function deleteAccountAction(): Action
    {
        return DeleteAccountAction::make()->record(
            fn () => Filament::auth()->user()
        );
    }
}
