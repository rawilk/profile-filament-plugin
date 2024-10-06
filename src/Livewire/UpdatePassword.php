<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Rawilk\FilamentPasswordInput\Password as PasswordInput;
use Rawilk\ProfileFilament\Contracts\UpdatePasswordAction;
use Rawilk\ProfileFilament\Features;

/**
 * @property-read null|string $passwordResetUrl
 * @property-read Features $pluginFeatures
 * @property-read Form $form
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

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <x-filament-panels::form
                wire:submit="updatePassword"
                :wire:key="$this->getId() . '.forms.data'"
            >
                {{ $this->form }}
            </x-filament-panels::form>
        </div>
        HTML;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('profile-filament::pages/security.password.title'))
                    ->schema([
                        $this->getCurrentPasswordField(),
                        $this->getPasswordField(),
                        $this->getPasswordConfirmationField(),

                        Forms\Components\Actions::make([
                            $this->submitAction(),
                            $this->forgotPasswordLinkAction(),
                        ]),

                        Forms\Components\Placeholder::make('help')
                            ->label('')
                            ->hiddenLabel()
                            ->content(
                                fn (): Htmlable => new HtmlString(Blade::render(<<<'HTML'
                                <div class="text-xs gap-x-1 flex items-start">
                                    <div class="shrink-0">
                                        <x-filament::icon
                                            alias="profile-filament::help"
                                            icon="heroicon-o-question-mark-circle"
                                            class="h-4 w-4"
                                        />
                                    </div>

                                    <div class="flex-1">
                                        {{ str(__('profile-filament::pages/security.password.form.form_info'))->markdown()->toHtmlString() }}
                                    </div>
                                </div>
                                HTML)),
                            ),
                    ]),
            ])
            ->statePath('data');
    }

    public function submitAction(): Forms\Components\Actions\Action
    {
        return Forms\Components\Actions\Action::make('submit')
            ->action('updatePassword')
            ->color('primary')
            ->submit('updatePassword')
            ->label(__('profile-filament::pages/security.password.form.save_button'));
    }

    public function forgotPasswordLinkAction(): Forms\Components\Actions\Action
    {
        return Forms\Components\Actions\Action::make('forgotPassword')
            ->label(__('profile-filament::pages/security.password.form.forgot_password_link'))
            ->link()
            ->color('primary')
            ->url($this->passwordResetUrl)
            ->visible(fn (): bool => filled($this->passwordResetUrl));
    }

    public function updatePassword(UpdatePasswordAction $updater): void
    {
        $updater(Filament::auth()->user(), $this->form->getState()['password']);

        $this->form->fill();

        $this->getSuccessNotification()?->send();
    }

    protected function getSuccessNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('profile-filament::pages/security.password.form.notification'));
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
