<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Infolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Rawilk\ProfileFilament\Filament\Actions\Users\DeleteAccountInfolistAction;

/**
 * In the majority of applications, this component will probably be overridden
 * so application specific logic can be applied to it as necessary.
 */
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record(filament()->auth()->user())
            ->schema([
                Infolists\Components\Section::make(__('profile-filament::pages/settings.delete_account.title'))
                    ->icon('heroicon-o-exclamation-circle')
                    ->iconColor('danger')
                    ->schema([
                        Infolists\Components\TextEntry::make('desc')
                            ->label('')
                            ->hiddenLabel()
                            ->default(__('profile-filament::pages/settings.delete_account.description')),

                        Infolists\Components\Actions::make([
                            $this->deleteAction(),
                        ]),
                    ]),
            ]);
    }

    public function deleteAction(): Infolists\Components\Actions\Action
    {
        return DeleteAccountInfolistAction::make();
    }
}
