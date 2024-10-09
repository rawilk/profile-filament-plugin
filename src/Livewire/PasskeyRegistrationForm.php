<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Timebox;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\Passkeys\RegisterPasskeyAction;
use Rawilk\ProfileFilament\Contracts\Passkeys\UpgradeToPasskeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AttestationFailed;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @property-read bool $isUpgrading
 * @property-read bool $userHasMfaEnabled
 * @property-read User&\Rawilk\ProfileFilament\Concerns\TwoFactorAuthenticatable $user
 * @property Form $form
 */
class PasskeyRegistrationForm extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use UsesSudoChallengeAction;

    #[Locked]
    public ?WebauthnKey $upgrading = null;

    public ?array $data = [];

    #[Computed]
    public function userHasMfaEnabled(): bool
    {
        return Mfa::userHasMfaEnabled();
    }

    #[Computed]
    public function user(): User
    {
        return filament()->auth()->user();
    }

    #[Computed]
    public function isUpgrading(): bool
    {
        return $this->upgrading !== null;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function render(): string
    {
        return <<<'HTML'
        <div class="-mt-3">
            <div
                x-data="{
                    exclude: @js([$upgrading?->getKey()]),
                    isUpgrading: @js($this->isUpgrading),
                }"
            >
                <x-profile-filament::webauthn-script
                    mode="register"
                    x-data="registerWebauthn({
                        registerUrl: {{ Js::from(route('profile-filament::webauthn.passkey_attestation_pk')) }},
                        registerData: function () {
                            return this.isUpgrading ? { exclude: this.exclude } : {};
                        },
                        before: function () {
                            if (this.isUpgrading) {
                                return true;
                            }

                            return $wire.validate()
                                .then(() => ! this.hasErrors());
                        },
                    })"
                    :id="$this->getId() . '.passkey-register-form'"
                    wire:ignore.self
                >
                    <div
                        class="text-sm text-left text-pretty"
                        x-show="browserSupportsWebAuthn"
                    >
                        <div class="space-y-3">
                            @if ($upgrading)
                                <p
                                    id="webauthn-intro-{{ $upgrading->getRouteKey() }}"
                                >
                                    {{
                                        str(__('profile-filament::pages/security.passkeys.actions.upgrade.intro', [
                                            'name' => e($upgrading->name),
                                        ]))
                                            ->inlineMarkdown()
                                            ->toHtmlString()
                                    }}
                                </p>
                            @else
                                <p wire:ignore>{{ __('profile-filament::pages/security.passkeys.actions.add.intro') }}</p>
                            @endif

                            <p wire:ignore>{{ __('profile-filament::pages/security.passkeys.actions.add.intro_line2') }}</p>

                            @unless($this->userHasMfaEnabled)
                                <p>
                                    {{ str(__('profile-filament::pages/security.passkeys.actions.add.mfa_disabled_notice'))->inlineMarkdown()->toHtmlString() }}
                                </p>
                            @endunless
                        </div>
                    </div>

                    @include('profile-filament::livewire.partials.webauthn-unsupported')

                    <div x-show="browserSupportsWebAuthn" class="w-full mt-4">
                        <div
                            x-show="! processing"
                        >
                            {{ $this->form }}

                            <div class="mt-4">
                                {{-- errors --}}
                                <x-profile-filament::register-webauthn-errors
                                    :error-message="__('profile-filament::pages/security.passkeys.actions.add.register_fail')"
                                    class="mb-4"
                                />

                                {{-- actions --}}
                                <div class="space-y-3">
                                    {{ $this->startWebauthnAction }}

                                    @if ($this->isUpgrading)
                                        {{ $this->cancelUpgradeAction }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <x-profile-filament::webauthn-waiting-indicator
                            x-show="processing"
                            style="display: none;"
                            x-cloak
                            wire:ignore
                            :message="__('profile-filament::pages/security.mfa.webauthn.actions.register.waiting')"
                        />
                    </div>
                </x-profile-filament::webauthn-script>
            </div>
        </div>
        HTML;
    }

    public function form(Form $form): Form
    {
        return $form
            ->operation('register')
            ->statePath('data')
            ->schema([
                $this->getNameInput(),
            ]);
    }

    public function startWebauthnAction(): Action
    {
        return Action::make('startWebauthn')
            ->label(
                fn (): string => $this->isUpgrading
                    ? __('profile-filament::pages/security.passkeys.actions.upgrade.prompt_button')
                    : __('profile-filament::pages/security.passkeys.actions.add.prompt_button')
            )
            ->color('primary')
            ->alpineClickHandler('register')
            ->extraAttributes([
                'class' => 'w-full',
            ]);
    }

    public function cancelUpgradeAction(): Action
    {
        return Action::make('cancelUpgrade')
            ->label(__('profile-filament::pages/security.passkeys.actions.upgrade.cancel_upgrade'))
            ->color('gray')
            ->alpineClickHandler(<<<'JS'
            error = null;
            isUpgrading = false;
            $wire.cancelUpgrade();
            JS)
            ->extraAttributes([
                'class' => 'w-full',
            ]);
    }

    public function cancelUpgrade(): void
    {
        $this->upgrading = null;

        unset($this->isUpgrading);
    }

    #[On(MfaEvent::WebauthnKeyDeleted->value)]
    public function onKeyDeleted(): void
    {
        $this->upgrading = null;

        unset($this->isUpgrading, $this->userHasMfaEnabled);
    }

    public function verifyKey(array $attestation): void
    {
        if (! $this->ensureSudoIsActive()) {
            return;
        }

        if ($this->upgrading) {
            abort_unless(
                Gate::allows('upgradeToPasskey', $this->upgrading),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($attestation) {
            try {
                $publicKeyCredentialSource = Webauthn::verifyAttestation(
                    attestationResponse: $attestation,
                    storedPublicKey: session()->pull(MfaSession::PasskeyAttestationPk->value),
                );
            } catch (AttestationFailed|Throwable) {
                $this->getFailureNotification()?->send();

                return;
            }

            // Flag for our listener in parent component to know if recovery codes
            // should be shown or not.
            $enabledMfa = ! Mfa::userHasMfaEnabled();

            if ($this->upgrading) {
                $passkey = app(UpgradeToPasskeyAction::class)(
                    user: $this->user,
                    publicKeyCredentialSource: $publicKeyCredentialSource,
                    attestation: $attestation,
                    webauthnKey: $this->upgrading,
                );
            } else {
                $passkey = app(RegisterPasskeyAction::class)(
                    user: $this->user,
                    publicKeyCredentialSource: $publicKeyCredentialSource,
                    attestation: $attestation,
                    keyName: $this->form->getState()['name'],
                );
            }

            $this->dispatch(MfaEvent::PasskeyRegistered->value, id: $passkey->getKey(), name: $passkey->name, enabledMfa: $enabledMfa);

            if ($this->upgrading) {
                $this->dispatch(MfaEvent::WebauthnKeyUpgradedToPasskey->value, id: $passkey->getKey(), upgradedFrom: $this->upgrading->getKey());
            }

            $this->getSuccessNotification($passkey)?->send();

            $this->upgrading = null;

            $timebox->returnEarly();
        }, microseconds: 300 * 1000);
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

    protected function getNameInput(): TextInput
    {
        return TextInput::make('name')
            ->label(__('profile-filament::pages/security.passkeys.actions.add.name_field'))
            ->placeholder(__('profile-filament::pages/security.passkeys.actions.add.name_field_placeholder'))
            ->id($this->getId() . '.passkey-name')
            ->required()
            ->maxLength(255)
            ->unique(
                table: config('profile-filament.table_names.webauthn_key'),
                modifyRuleUsing: function (Unique $rule) {
                    $rule->where('user_id', $this->user->getKey());
                },
            )
            ->autocomplete('off')
            ->visible(fn (): bool => $this->upgrading === null)
            ->extraAttributes([
                'x-on:keydown.enter.prevent.stop' => 'register',
            ]);
    }

    protected function getFailureNotification(): ?Notification
    {
        return Notification::make()
            ->danger()
            ->title(__('profile-filament::pages/security.passkeys.actions.add.register_fail_notification'))
            ->persistent();
    }

    protected function getSuccessNotification(WebauthnKey $passkey): ?Notification
    {
        $notificationTitle = $this->upgrading
            ? __('profile-filament::pages/security.passkeys.actions.upgrade.success.title', ['name' => $passkey->name])
            : __('profile-filament::pages/security.passkeys.actions.add.success.title');

        $notificationBody = $this->upgrading
            ? __('profile-filament::pages/security.passkeys.actions.upgrade.success.description', ['app_name' => config('app.name'), 'name' => $passkey->name])
            : __('profile-filament::pages/security.passkeys.actions.add.success.description', ['app_name' => config('app.name')]);

        return Notification::make()
            ->success()
            ->title($notificationTitle)
            ->body($notificationBody)
            ->persistent();
    }
}
