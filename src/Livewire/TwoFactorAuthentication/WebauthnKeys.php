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
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Timebox;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\Webauthn\RegisterWebauthnKeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AttestationFailed;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Throwable;

/**
 * @property-read \Filament\Forms\Form $form
 * @property-read \Illuminate\Support\Collection $sortedWebauthnKeys
 * @property-read User $user
 */
class WebauthnKeys extends ProfileComponent
{
    use UsesSudoChallengeAction;

    #[Locked]
    public bool $show = false;

    #[Locked]
    public bool $showForm = false;

    /**
     * @var \Illuminate\Support\Collection<int, \Rawilk\ProfileFilament\Models\WebauthnKey>
     */
    #[Reactive]
    public Collection $webauthnKeys;

    /**
     * The data for a new security key.
     */
    public array $data = [];

    #[Computed]
    public function sortedWebauthnKeys(): Collection
    {
        return $this->webauthnKeys
            ->sortByDesc('created_at');
    }

    #[Computed]
    public function user(): User
    {
        return filament()->auth()->user();
    }

    public function messages(): array
    {
        return [
            'data.name.unique' => __('profile-filament::pages/security.passkeys.unique_validation_error'),
        ];
    }

    #[On(MfaEvent::ToggleWebauthnKeys->value)]
    public function toggle(bool $show): void
    {
        $this->show = $show;

        if ($this->show && $this->webauthnKeys->isEmpty()) {
            $this->initializeForm();
        }
    }

    public function initializeForm(): void
    {
        if ($this->showForm) {
            return;
        }

        $this->form->fill();

        $this->showForm = true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameField(),
            ])
            ->statePath('data');
    }

    public function addAction(): Action
    {
        return Action::make('add')
            ->color('gray')
            ->label(__('profile-filament::pages/security.mfa.webauthn.actions.register.trigger'))
            ->action(fn () => $this->initializeForm())
            ->mountUsing(function () {
                $this->ensureSudoIsActive(returnAction: 'add');
            });
    }

    #[On(MfaEvent::WebauthnKeyDeleted->value)]
    public function onKeyDeleted(): void
    {
        $this->showForm = false;
        session()->forget(MfaSession::AttestationPublicKey->value);
    }

    public function verifyKey(array $attestation, RegisterWebauthnKeyAction $action): void
    {
        try {
            $this->ensureSudoIsActive(returnAction: 'add');
        } catch (Halt) {
            return;
        }

        App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($attestation, $action) {
            $data = $this->form->getState();

            // Flag for our listener in parent component to know if recovery codes
            // should be shown or not.
            /** @phpstan-ignore-next-line */
            $enabledMfa = ! filament()->auth()->user()->two_factor_enabled;

            try {
                $publicKeyCredentialSource = Webauthn::verifyAttestation($attestation, unserialize(session()->pull(MfaSession::AttestationPublicKey->value)));
            } catch (AttestationFailed|Throwable) {
                Notification::make()
                    ->danger()
                    ->title(__('profile-filament::pages/security.mfa.webauthn.actions.register.register_fail_notification'))
                    ->send();

                return;
            }

            $webauthnKey = $action(
                user: filament()->auth()->user(),
                publicKeyCredentialSource: $publicKeyCredentialSource,
                attestation: $attestation,
                keyName: $data['name'],
            );

            $this->showForm = false;

            Notification::make()
                ->success()
                ->title(__('profile-filament::pages/security.mfa.webauthn.actions.register.success'))
                ->send();

            $this->dispatch(MfaEvent::WebauthnKeyAdded->value, enabledMfa: $enabledMfa, keyId: $webauthnKey->getKey());

            $timebox->returnEarly();
        }, microseconds: 300 * 1000);
    }

    protected function getNameField(): Component
    {
        return TextInput::make('name')
            ->required()
            ->label(__('profile-filament::pages/security.mfa.webauthn.actions.register.name'))
            ->hiddenLabel(true)
            ->placeholder(__('profile-filament::pages/security.mfa.webauthn.actions.register.name_placeholder'))
            ->maxLength(255)
            ->id('webauthn-register-name')
            ->unique(
                table: config('profile-filament.table_names.webauthn_key'),
                modifyRuleUsing: function (Unique $rule) {
                    /** @phpstan-ignore-next-line */
                    $rule->where('user_id', $this->user->id);
                },
            )
            ->autocomplete('off')
            ->live(debounce: 500)
            ->suffixAction(
                FormAction::make('prompt')
                    ->livewireClickHandlerEnabled(false)
                    ->label(__('profile-filament::pages/security.mfa.webauthn.actions.register.prompt_trigger'))
                    ->link()
                    ->color('gray')
                    ->disabled(fn ($state) => blank($state))
                    ->extraAttributes([
                        'x-on:click' => 'submit',
                    ]),
            )
            ->inlineSuffix()
            ->extraInputAttributes([
                'x-on:keydown.enter.prevent.stop' => 'submit',
            ]);
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.two-factor-authentication.webauthn-keys';
    }
}
