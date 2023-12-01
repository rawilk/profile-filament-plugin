<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Rawilk\FilamentPasswordInput\Password as PasswordInput;
use Rawilk\ProfileFilament\Contracts\UpdatePasswordAction;
use Rawilk\ProfileFilament\Features;

/**
 * @property-read null|string $passwordResetUrl
 * @property-read \Rawilk\ProfileFilament\Features $pluginFeatures
 * @property-read \Filament\Forms\Form $form
 */
class UpdatePassword extends ProfileComponent
{
    public ?array $data = [];

    #[Computed]
    public function passwordResetUrl(): ?string
    {
        if (! $this->pluginFeatures->shouldShowPasswordResetLink()) {
            return null;
        }

        return Filament::getRequestPasswordResetUrl();
    }

    #[Computed]
    public function pluginFeatures(): Features
    {
        return $this->profilePlugin->panelFeatures();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getCurrentPasswordField(),
                $this->getPasswordField(),
                $this->getPasswordConfirmationField(),
            ])
            ->statePath('data');
    }

    public function submitAction(): Action
    {
        return Action::make('submit')
            ->action('updatePassword')
            ->color('primary')
            ->submit('updatePassword')
            ->label(__('profile-filament::pages/security.password.form.save_button'));
    }

    public function updatePassword(UpdatePasswordAction $updater): void
    {
        $updater(Filament::auth()->user(), $this->form->getState()['password']);

        $this->form->fill();

        Notification::make()
            ->success()
            ->title(__('profile-filament::pages/security.password.form.notification'))
            ->send();
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.update-password';
    }

    protected function getCurrentPasswordField(): Component
    {
        return PasswordInput::make('current_password')
            ->label(__('profile-filament::pages/security.password.form.current_password'))
            ->required()
            ->autocomplete('current-password')
            ->currentPassword()
            ->maxWidth('lg')
            ->visible(fn (): bool => $this->pluginFeatures->requiresCurrentPassword());
    }

    protected function getPasswordField(): Component
    {
        return PasswordInput::make('password')
            ->label(__('profile-filament::pages/security.password.form.password'))
            ->copyable()
            ->regeneratePassword()
            ->inlineSuffix()
            ->required()
            ->autocomplete('new-password')
            ->rule(Password::defaults())
            ->dehydrateStateUsing(
                fn (string $state): string => config('profile-filament.hash_user_passwords')
                    ? Hash::make($state)
                    : $state
            )
            ->maxWidth('lg');
    }

    protected function getPasswordConfirmationField(): Component
    {
        return PasswordInput::make('password_confirmation')
            ->label(__('profile-filament::pages/security.password.form.password_confirmation'))
            ->hidePasswordManagerIcons()
            ->required()
            ->visible(fn (): bool => $this->pluginFeatures->requiresPasswordConfirmation())
            ->same('password')
            ->maxWidth('lg');
    }
}
