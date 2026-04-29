<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use LogicException;
use Rawilk\FilamentPasswordInput\Password as PasswordInput;
use Rawilk\ProfileFilament\Contracts\UpdatePasswordAction;
use Rawilk\ProfileFilament\Support\Config;
use Throwable;

/**
 * @property-read null|string $passwordResetUrl
 */
class UpdatePassword extends ProfileComponent
{
    use CanUseDatabaseTransactions;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    #[Computed]
    public function passwordResetUrl(): ?string
    {
        if (! $this->profilePlugin->shouldShowPasswordResetLinkInUpdatePasswordForm()) {
            return null;
        }

        return Filament::getRequestPasswordResetUrl();
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->content }}
        </div>
        HTML;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('updatePasswordForm')
            ->livewireSubmitHandler('updatePassword');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model($this->getUser())
            ->operation('edit')
            ->statePath('data')
            ->components($this->getFormSchema());
    }

    public function getUser(): Authenticatable&Model
    {
        $user = Filament::auth()->user();

        throw_unless(
            $user instanceof Model,
            new LogicException('The authenticated user object must be an Eloquent model to allow this form to update it.'),
        );

        return $user;
    }

    public function updatePassword(UpdatePasswordAction $updater): void
    {
        $rateLimitingKey = 'pf-update-password:' . Filament::auth()->id();

        if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
            $this->getRateLimitedNotification(new TooManyRequestsException(
                static::class,
                'updatePassword',
                request()->ip(),
                RateLimiter::availableIn($rateLimitingKey),
            ))?->send();

            return;
        }

        RateLimiter::hit($rateLimitingKey);

        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            $data = $this->mutateFormDataBeforeSave($data);

            $user = $this->handleRecordUpdate($this->getUser(), $data);
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        if (request()->hasSession()) {
            request()->session()->put([
                'password_hash_' . Filament::getAuthGuard() => $user->getAuthPassword(),
            ]);
        }

        $this->form->fill();

        $this->getSuccessNotification()?->send();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        app(UpdatePasswordAction::class)($record, $data['password']);

        return $record;
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make(__('profile-filament::pages/security.password.title'))
                ->schema([
                    $this->getPasswordComponent(),
                    $this->getPasswordConfirmationComponent(),
                    $this->getCurrentPasswordComponent(),
                ])
                ->footer([
                    Actions::make([
                        $this->getSaveFormAction(),
                        $this->getForgotPasswordLinkAction(),
                    ])
                        ->key('update-password-form-actions'),
                ]),
        ];
    }

    protected function getSuccessNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('profile-filament::pages/security.password.form.notifications.saved.title'))
            ->success();
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('profile-filament::pages/security.password.form.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(__('profile-filament::pages/security.password.form.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->danger();
    }

    protected function getCurrentPasswordComponent(): Component
    {
        return PasswordInput::make('current_password')
            ->label(__('profile-filament::pages/security.password.form.current_password.label'))
            ->validationAttribute(__('profile-filament::pages/security.password.form.current_password.validation_attribute'))
            ->belowContent(__('profile-filament::pages/security.password.form.current_password.below_content'))
            ->required()
            ->autocomplete('current-password')
            ->currentPassword(guard: Filament::getAuthGuard())
            ->maxWidth(Width::Large)
            ->dehydrated(false)
            ->visible(fn (Get $get): bool => filled($get('password')) && $this->profilePlugin->isCurrentPasswordRequired());
    }

    protected function getPasswordComponent(): Component
    {
        return PasswordInput::make('password')
            ->label(__('profile-filament::pages/security.password.form.password.label'))
            ->validationAttribute(__('profile-filament::pages/security.password.form.password.validation_attribute'))
            ->copyable()
            ->regeneratePassword()
            ->inlineSuffix()
            ->required()
            ->autocomplete('new-password')
            ->rule(Password::defaults())
            ->showAllValidationMessages()
            ->dehydrateStateUsing(
                fn (string $state): string => Config::hashUserPasswords()
                    ? Hash::make($state)
                    : $state
            )
            ->live(debounce: 500)
            ->maxWidth(Width::Large);
    }

    protected function getPasswordConfirmationComponent(): Component
    {
        return PasswordInput::make('password_confirmation')
            ->label(__('profile-filament::pages/security.password.form.password_confirmation.label'))
            ->validationAttribute(__('profile-filament::pages/security.password.form.password_confirmation.validation_attribute'))
            ->hidePasswordManagerIcons()
            ->autocomplete('new-password')
            ->required()
            ->visible(fn (): bool => $this->profilePlugin->isPasswordConfirmationRequired())
            ->same('password')
            ->maxWidth(Width::Large)
            ->dehydrated(false);
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('profile-filament::pages/security.password.form.actions.save.label'))
            ->submit('updatePassword')
            ->keyBindings(['mod+s']);
    }

    protected function getForgotPasswordLinkAction(): Action
    {
        return Action::make('forgotPassword')
            ->label(__('profile-filament::pages/security.password.form.actions.forgot_password.label'))
            ->link()
            ->url($this->passwordResetUrl)
            ->visible(fn (Get $get): bool => filled($get('password')) && filled($this->passwordResetUrl));
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
}
