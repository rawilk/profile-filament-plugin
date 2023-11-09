<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Rawilk\FilamentPasswordInput\Password as PasswordInput;
use Rawilk\ProfileFilament\Contracts\UpdatePasswordAction;
use Rawilk\ProfileFilament\Enums\Livewire\SensitiveProfileSection;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property-read bool $shouldShowForm
 */
class UpdatePassword extends ProfileComponent
{
    public ?array $data = [];

    #[Computed]
    public function shouldShowForm(): bool
    {
        return ProfileFilament::shouldShowProfileSection(SensitiveProfileSection::UpdatePassword->value);
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

    public function updatePassword(UpdatePasswordAction $updater): void
    {
        abort_unless(
            ProfileFilament::shouldShowProfileSection(SensitiveProfileSection::UpdatePassword->value),
            Response::HTTP_FORBIDDEN,
        );

        $password = $this->form->getState()['password'];
        if (config('profile-filament.hash_user_passwords')) {
            $password = Hash::make($password);
        }

        $updater(Filament::auth()->user(), $password);

        $this->reset(['data']);

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
            ->rule('current_password')
            ->maxWidth('lg')
            ->visible($this->profilePlugin->isUpdatePasswordFieldEnabled('current_password'));
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
            ->maxWidth('lg');
    }

    protected function getPasswordConfirmationField(): Component
    {
        return PasswordInput::make('password_confirmation')
            ->label(__('profile-filament::pages/security.password.form.password_confirmation'))
            ->hidePasswordManagerIcons()
            ->required()
            ->visible($this->profilePlugin->isUpdatePasswordFieldEnabled('password_confirmation'))
            ->same('password')
            ->maxWidth('lg');
    }
}
