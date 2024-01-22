<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\DeleteAccountAction;

/**
 * In the majority of applications, this component will probably be overridden
 * so application specific logic can be applied to it as necessary.
 */
class DeleteAccount extends ProfileComponent
{
    use UsesSudoChallengeAction;

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->color('danger')
            ->label(__('profile-filament::pages/settings.delete_account.actions.delete.trigger'))
            ->requiresConfirmation()
            ->modalSubmitActionLabel(__('profile-filament::pages/settings.delete_account.actions.delete.submit_button'))
            ->modalIcon(FilamentIcon::resolve('actions::delete-action.modal') ?? 'heroicon-o-trash')
            ->modalHeading(__('profile-filament::pages/settings.delete_account.actions.delete.modal_title'))
            ->modalDescription(__('profile-filament::pages/settings.delete_account.description'))
            ->form([
                // Even though we're requiring "sudo" mode to do this, we want the user to enter their email
                // address in, so they're more likely to be conscious of what they're doing.
                $this->getEmailInput(),
            ])
            ->action(function (DeleteAccountAction $deleter) {
                $deleter(Filament::auth()->user()->fresh());

                Filament::auth()->logout();

                session()->invalidate();
                session()->regenerateToken();

                session()->flash('success', __('profile-filament::pages/settings.delete_account.actions.delete.success'));

                redirect()->to(Filament::getLoginUrl());
            })
            ->mountUsing(function () {
                $this->ensureSudoIsActive(returnAction: 'delete');
            });
    }

    protected function getEmailInput(): Component
    {
        return TextInput::make('email')
            /** @phpstan-ignore-next-line */
            ->label(fn () => __('profile-filament::pages/settings.delete_account.actions.delete.email_label', ['email' => Filament::auth()->user()?->email]))
            ->required()
            ->email()
            ->rules([
                fn (): Closure => function (string $attribute, $value, Closure $fail) {
                    /** @phpstan-ignore-next-line */
                    if (Str::lower($value) !== Str::lower(Filament::auth()->user()->email)) {
                        $fail(__('profile-filament::pages/settings.delete_account.actions.delete.incorrect_email'));
                    }
                },
            ]);
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.delete-account';
    }
}
