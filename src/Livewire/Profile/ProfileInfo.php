<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Profile;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Rawilk\ProfileFilament\Events\Profile\ProfileInformationUpdated;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;

/**
 * This component is meant to be extended by your own component,
 * so it's very basic on purpose.
 */
class ProfileInfo extends ProfileComponent implements HasInfolists
{
    use InteractsWithInfolists;

    public function infoList(Infolist $infolist): Infolist
    {
        return $infolist
            ->record(Filament::auth()->user())
            ->schema($this->infolistSchema());
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->label(__('profile-filament::pages/profile.info.actions.edit.trigger'))
            ->color('primary')
            ->size('sm')
            ->record(Filament::auth()->user())
            ->fillForm(fn (): array => $this->getFormData())
            ->form($this->formSchema())
            ->action(function (Form $form) {
                $this->saveForm($form);

                ProfileInformationUpdated::dispatch(Filament::auth()->user());

                Notification::make()
                    ->success()
                    ->title(__('profile-filament::pages/profile.info.actions.edit.success'))
                    ->send();
            })
            ->modalHeading(__('profile-filament::pages/profile.info.actions.edit.modal_title'))
            ->modalSubmitActionLabel(__('profile-filament::pages/profile.info.actions.edit.submit'));
    }

    protected function saveForm(Form $form): void
    {
        Filament::auth()->user()->forceFill($form->getState())->save();
    }

    protected function infolistSchema(): array
    {
        return [
            $this->nameTextEntry(),
            $this->createdAtTextEntry(),
        ];
    }

    protected function formSchema(): array
    {
        return [
            $this->nameInput(),
        ];
    }

    protected function nameTextEntry(): Entry
    {
        return TextEntry::make('name')
            ->label(__('profile-filament::pages/profile.info.name.label'));
    }

    protected function nameInput(): Component
    {
        return TextInput::make('name')
            ->label(__('profile-filament::pages/profile.info.name.form_label'))
            ->required()
            ->maxLength(255);
    }

    protected function createdAtTextEntry(): Entry
    {
        return TextEntry::make('created_at')
            ->label(__('profile-filament::pages/profile.info.created_at.label'))
            ->dateTime(
                format: 'M j, Y',
                timezone: ProfileFilament::userTimezone(),
            );
    }

    protected function getFormData(): array
    {
        return Filament::auth()->user()->toArray();
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.profile.profile-info';
    }
}
