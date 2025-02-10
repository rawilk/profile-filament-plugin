<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication;

use Filament\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
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
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Rawilk\ProfileFilament\Filament\Actions\Mfa\AddWebauthnKeyAction;
use Rawilk\ProfileFilament\Livewire\ProfileComponent;
use Throwable;

/**
 * @property-read Form $form
 * @property-read Collection $sortedWebauthnKeys
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
     * @var Collection<int, \Rawilk\ProfileFilament\Models\WebauthnKey>
     */
    #[Reactive]
    public Collection $webauthnKeys;

    /**
     * The data for a new security key.
     */
    public ?array $data = [];

    #[Computed]
    public function sortedWebauthnKeys(): Collection
    {
        return $this
            ->webauthnKeys
            ->sortByDesc('created_at');
    }

    #[Computed]
    public function user(): User
    {
        return filament()->auth()->user();
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            @if ($show)
                <div>
                    <div
                        id="webauthn-keys-list"
                        @class([
                            'mb-4 border-b border-gray-300 dark:border-gray-600' => $this->sortedWebauthnKeys->isNotEmpty(),
                            'divide-y divide-gray-300 dark:divide-gray-600',
                        ])
                    >
                        @foreach ($this->sortedWebauthnKeys as $webauthnKey)
                            @livewire(\Rawilk\ProfileFilament\Livewire\TwoFactorAuthentication\WebauthnKey::class, [
                                'id' => $webauthnKey->getKey(),
                            ], key('webauthnKey' . $webauthnKey->getKey()))
                        @endforeach
                    </div>

                    <div
                        @class([
                            'mt-4' => $this->sortedWebauthnKeys->isEmpty(),
                        ])
                    >
                        @unless ($showForm)
                            {{ $this->addAction }}
                        @endunless

                        @includeWhen($showForm, 'profile-filament::livewire.partials.webauthn-key-register-form')
                    </div>
                </div>

                <x-filament-actions::modals />
            @endif
        </div>
        HTML;
    }

    /**
     * ->validationMessages() is not rendering the unique error message for some reason
     * on the text input, so we'll defer to this instead.
     */
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

        if (! $this->show) {
            $this->showForm = false;
        }

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

        $this->resetValidation();

        $this->showForm = true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->operation('register')
            ->statePath('data')
            ->schema([
                $this->getNameField(),
            ]);
    }

    public function addAction(): Action
    {
        return AddWebauthnKeyAction::make('add')
            ->action(fn () => $this->initializeForm());
    }

    public function retryWebauthnAction(): Action
    {
        return Action::make('retryWebauthn')
            ->color('gray')
            ->size(ActionSize::Small)
            ->label(__('profile-filament::pages/security.mfa.webauthn.actions.register.retry_button'))
            ->alpineClickHandler('register');
    }

    #[On(MfaEvent::WebauthnKeyDeleted->value)]
    public function onKeyDeleted(): void
    {
        $this->showForm = false;

        session()->forget(MfaSession::AttestationPublicKey->value);
    }

    public function verifyKey(array $attestation, RegisterWebauthnKeyAction $action): void
    {
        if (! $this->ensureSudoIsActive()) {
            return;
        }

        App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($attestation, $action) {
            $data = $this->form->getState();

            // Flag for our listener in parent component to know if recovery codes
            // should be shown or not.
            $enabledMfa = ! Mfa::userHasMfaEnabled();

            try {
                $publicKeyCredentialSource = Webauthn::verifyAttestation(
                    $attestation,
                    session()->pull(MfaSession::AttestationPublicKey->value),
                );
            } catch (AttestationFailed|Throwable) {
                Notification::make()
                    ->danger()
                    ->title(__('profile-filament::pages/security.mfa.webauthn.actions.register.register_fail_notification'))
                    ->persistent()
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
            ->hiddenLabel()
            ->placeholder(__('profile-filament::pages/security.mfa.webauthn.actions.register.name_placeholder'))
            ->maxLength(255)
            ->id($this->getId() . '.webauthn-register-name')
            ->unique(
                table: config('profile-filament.table_names.webauthn_key'),
                modifyRuleUsing: function (Unique $rule) {
                    $rule->where('user_id', $this->user->getKey());
                },
            )
            ->autocomplete('off')
            ->live(debounce: 500)
            ->suffixAction(
                FormAction::make('prompt')
                    ->label(__('profile-filament::pages/security.mfa.webauthn.actions.register.prompt_trigger'))
                    ->link()
                    ->color('gray')
                    ->disabled(fn ($state) => blank($state))
                    ->alpineClickHandler('register'),
            )
            ->inlineSuffix()
            ->extraInputAttributes([
                'x-on:keydown.enter.prevent.stop' => 'register',
            ]);
    }
}
