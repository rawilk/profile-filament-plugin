<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication;

use Filament\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\Support\Timebox;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\AuthenticatorApps\ConfirmTwoFactorAppAction;
use Rawilk\ProfileFilament\Contracts\AuthenticatorAppService;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;

/**
 * @property-read AuthenticatorAppService $authenticatorService
 * @property-read bool $showCodeError
 * @property-read Authenticatable $user
 * @property-read Collection $sortedAuthenticatorApps
 * @property-read Form $form
 */
class AuthenticatorAppForm extends ProfileComponent
{
    use UsesSudoChallengeAction;

    #[Locked]
    public bool $show = false;

    #[Locked]
    public bool $showForm = false;

    #[Locked]
    public string $secret = '';

    #[Locked]
    public string $qrCodeUrl = '';

    public string $code = '';

    public string $name = '';

    #[Locked]
    public bool $codeValid = false;

    /** @var \Illuminate\Support\Collection<int, \Rawilk\ProfileFilament\Models\AuthenticatorApp> */
    #[Reactive]
    public Collection $authenticatorApps;

    #[Computed]
    public function sortedAuthenticatorApps(): Collection
    {
        return $this->authenticatorApps
            ->sortByDesc('created_at');
    }

    #[Computed]
    public function user(): Authenticatable
    {
        return filament()->auth()->user();
    }

    #[Computed]
    public function authenticatorService(): AuthenticatorAppService
    {
        return app(AuthenticatorAppService::class);
    }

    #[Computed]
    public function showCodeError(): bool
    {
        return filled($this->code) && ! $this->codeValid;
    }

    #[On(MfaEvent::ShowAppForm->value)]
    public function showApps(): void
    {
        $this->reset('secret', 'qrCodeUrl', 'code');

        $this->show = true;
        $this->showForm = $this->authenticatorApps->isEmpty();

        if ($this->showForm) {
            $this->showAddForm();
        }
    }

    public function showAddForm(): void
    {
        $this->reset('code', 'codeValid');

        $this->secret = $this->authenticatorService->generateSecretKey();
        $this->qrCodeUrl = $this->authenticatorService->qrCodeUrl(
            companyName: config('app.name'),
            companyEmail: $this->user->email,
            secret: $this->secret,
        );

        $this->name = __('profile-filament::pages/security.mfa.app.default_device_name');

        $this->showForm = true;
    }

    public function confirm(ConfirmTwoFactorAppAction $action): void
    {
        try {
            $this->ensureSudoIsActive(returnAction: 'add');
        } catch (Halt) {
            Notification::make()
                ->danger()
                ->title(__('profile-filament::messages.sudo_challenge.expired'))
                ->send();

            return;
        }

        App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($action) {
            $data = $this->form->getState();
            $this->ensureCodeIsValid($data['code']);
            if (! $this->codeValid) {
                return;
            }

            // Flag for our listener in parent component to know if recovery codes
            // should be shown to the user or not.
            $enabledMfa = ! $this->user->two_factor_enabled;

            $action(filament()->auth()->user(), $data['name'], $this->secret);

            $this->cancelForm();

            $this->dispatch(MfaEvent::AppAdded->value, enabledMfa: $enabledMfa);

            $timebox->returnEarly();
        }, microseconds: 300 * 1000);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getSecretField(),
                $this->getNameField(),
                $this->getCodeField(),
            ]);
    }

    #[On(MfaEvent::HideAppList->value)]
    public function hideList(): void
    {
        $this->show = false;
    }

    public function addAction(): Action
    {
        return Action::make('add')
            ->color('gray')
            ->action(fn () => $this->showAddForm())
            ->label(__('profile-filament::pages/security.mfa.app.add_another_app_button'))
            ->mountUsing(function () {
                $this->ensureSudoIsActive(returnAction: 'add');
            });
    }

    public function submitAction(): Action
    {
        return Action::make('submit')
            ->label(__('profile-filament::pages/security.mfa.app.submit_code_confirmation'))
            ->disabled(fn () => ! $this->codeValid)
            ->submit('confirm');
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('profile-filament::pages/security.mfa.app.cancel_code_confirmation'))
            ->color('gray')
            ->action(fn () => $this->cancelForm());
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.two-factor-authentication.authenticator-app-form';
    }

    protected function getNameField(): Component
    {
        return TextInput::make('name')
            ->label(__('profile-filament::pages/security.mfa.app.device_name'))
            ->placeholder(__('profile-filament::pages/security.mfa.app.device_name_placeholder'))
            ->required()
            ->maxlength(255)
            ->autocomplete('off')
            ->maxWidth('xs')
            ->unique(
                table: config('profile-filament.table_names.authenticator_app'),
                modifyRuleUsing: function (Unique $rule) {
                    $rule->where('user_id', filament()->auth()->id());
                },
            )
            ->helperText(__('profile-filament::pages/security.mfa.app.device_name_help'));
    }

    protected function getCodeField(): Component
    {
        return TextInput::make('code')
            ->label(__('profile-filament::pages/security.mfa.app.code_confirmation_input'))
            ->placeholder(__('profile-filament::pages/security.mfa.app.code_confirmation_placeholder'))
            ->maxWidth('xs')
            ->autocomplete('off')
            ->debounce()
            ->required()
            ->extraInputAttributes([
                'pattern' => '[0-9]{6}',
            ])
            ->afterStateUpdated(function (?string $state) {
                if (blank($state)) {
                    $this->codeValid = false;

                    return;
                }

                $this->ensureCodeIsValid($state);
            });
    }

    protected function getSecretField(): Component
    {
        $copyMessage = Js::from(__('profile-filament::pages/security.mfa.app.copy_secret_confirmation'));

        return TextInput::make('secret')
            ->readOnly()
            ->maxWidth('xs')
            ->hiddenLabel()
            ->suffixAction(
                FormAction::make('copySecretToClipboard')
                    ->livewireClickHandlerEnabled(false)
                    ->icon('heroicon-m-clipboard')
                    ->tooltip(__('profile-filament::pages/security.mfa.app.copy_secret_tooltip'))
                    ->color('gray')
                    ->extraAttributes(fn () => [
                        'x-on:click' => new HtmlString(<<<JS
                        window.navigator.clipboard.writeText('{$this->secret}');
                        \$tooltip({$copyMessage}, { theme: \$store.theme })
                        JS),

                        'title' => '',
                    ])
            );
    }

    protected function cancelForm(): void
    {
        $this->reset('code', 'secret', 'name', 'qrCodeUrl', 'showForm');

        if ($this->authenticatorApps->isEmpty()) {
            $this->show = false;
            $this->dispatch(MfaEvent::HideAppForm->value);
        }
    }

    protected function isCodeValid(string $code): bool
    {
        return $this->authenticatorService->verify(
            secret: $this->secret,
            code: $code,
            withoutTimestamps: true
        );
    }

    protected function ensureCodeIsValid(string $code): void
    {
        $this->codeValid = $this->isCodeValid($code);
    }
}
