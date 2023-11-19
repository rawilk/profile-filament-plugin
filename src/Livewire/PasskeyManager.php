<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire;

use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Rawilk\ProfileFilament\Concerns\Sudo\UsesSudoChallengeAction;
use Rawilk\ProfileFilament\Contracts\Passkeys\RegisterPasskeyAction;
use Rawilk\ProfileFilament\Contracts\Passkeys\UpgradeToPasskeyAction;
use Rawilk\ProfileFilament\Enums\Livewire\MfaEvent;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AttestationFailed;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Rawilk\ProfileFilament\Models\WebauthnKey;
use Throwable;

/**
 * @property-read User $user
 */
class PasskeyManager extends ProfileComponent
{
    use UsesSudoChallengeAction;

    public ?string $name = null;

    public ?WebauthnKey $upgrading = null;

    protected $listeners = [
        MfaEvent::PasskeyDeleted->value => '$refresh',
    ];

    #[Computed]
    public function user(): User
    {
        return filament()->auth()->user();
    }

    public function messages(): array
    {
        return [
            'name.unique' => __('profile-filament::pages/security.passkeys.unique_validation_error'),
        ];
    }

    public function rendering($view): void
    {
        $view->with([
            'headerId' => Str::uuid(),
            'passkeys' => $this->user->passkeys,
        ]);
    }

    #[On(MfaEvent::WebauthnKeyDeleted->value)]
    public function onKeyDeleted($id): void
    {
        if ($id === $this->upgrading?->id) {
            $this->upgrading = null;
        }
    }

    #[On(MfaEvent::StartPasskeyUpgrade->value)]
    public function startUpgrade($id): void
    {
        $this->upgrading = app(config('profile-filament.models.webauthn_key'))::findOrFail($id);

        $this->mountAction('add', ['excludeId' => $id]);
    }

    public function cancelUpgrade(): void
    {
        $this->upgrading = null;
    }

    public function verifyKey(array $attestation): void
    {
        try {
            $this->ensureSudoIsActive('add');
        } catch (Halt) {
            return;
        }

        if ($this->upgrading) {
            $this->authorize('upgradeToPasskey', $this->upgrading);
        }

        App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($attestation) {
            try {
                $publicKeyCredentialSource = Webauthn::verifyAttestation(
                    attestationResponse: $attestation,
                    storedPublicKey: unserialize(session()->pull(MfaSession::PasskeyAttestationPk->value)),
                );
            } catch (AttestationFailed|Throwable) {
                Notification::make()
                    ->danger()
                    ->title(__('profile-filament::pages/security.passkeys.actions.add.register_fail_notification'))
                    ->send();

                return;
            }

            // Flag for our listener in the mfa overview component to know if recovery codes
            // should be shown or not.
            $enabledMfa = ! filament()->auth()->user()->two_factor_enabled;

            if ($this->upgrading) {
                $passkey = app(UpgradeToPasskeyAction::class)(
                    user: filament()->auth()->user(),
                    publicKeyCredentialSource: $publicKeyCredentialSource,
                    attestation: $attestation,
                    webauthnKey: $this->upgrading,
                );
            } else {
                $passkey = app(RegisterPasskeyAction::class)(
                    user: filament()->auth()->user(),
                    publicKeyCredentialSource: $publicKeyCredentialSource,
                    attestation: $attestation,
                    keyName: $this->name,
                );
            }

            $this->dispatch('close-modal', id: "{$this->getId()}-action");
            $this->dispatch(MfaEvent::PasskeyRegistered->value, id: $passkey->id, name: $passkey->name, enabledMfa: $enabledMfa);

            if ($this->upgrading) {
                $this->dispatch(MfaEvent::WebauthnKeyUpgradedToPasskey->value, id: $passkey->id, upgradedFrom: $this->upgrading->id);
            }

            $notificationTitle = $this->upgrading
                ? __('profile-filament::pages/security.passkeys.actions.upgrade.success.title', ['name' => $passkey->name])
                : __('profile-filament::pages/security.passkeys.actions.add.success.title');

            $notificationBody = $this->upgrading
                ? __('profile-filament::pages/security.passkeys.actions.upgrade.success.description', ['app_name' => config('app.name'), 'name' => $passkey->name])
                : __('profile-filament::pages/security.passkeys.actions.add.success.description', ['app_name' => config('app.name')]);

            $this->upgrading = null;

            Notification::make()
                ->success()
                ->title($notificationTitle)
                ->body($notificationBody)
                ->persistent()
                ->send();

            $timebox->returnEarly();
        }, microseconds: 300 * 1000);
    }

    public function addAction(): Action
    {
        return Action::make('add')
            ->label(__('profile-filament::pages/security.passkeys.actions.add.trigger'))
            ->color('primary')
            ->requiresConfirmation()
            ->modalIcon('pf-passkey')
            ->modalIconColor('primary')
            ->modalHeading(fn () => $this->upgrading ? __('profile-filament::pages/security.passkeys.actions.upgrade.modal_title') : __('profile-filament::pages/security.passkeys.actions.add.modal_title'))
            ->modalDescription(null)
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(fn (array $arguments): View => view(
                'profile-filament::livewire.partials.register-passkey',
                [
                    //                    'attestation' => $this->getAttestation($arguments['excludeId'] ?? null),
                    'upgrading' => $this->upgrading,
                ],
            ))
            ->mountUsing(function (array $arguments) {
                $this->ensureSudoIsActive('add');

                $this->form->fill();

                if (! Arr::has($arguments, 'excludeId') || blank($arguments['excludeId'])) {
                    $this->upgrading = null;
                }
            });
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameField(),
            ]);
    }

    protected function getNameField(): Component
    {
        return TextInput::make('name')
            ->label(__('profile-filament::pages/security.passkeys.actions.add.name_field'))
            ->placeholder(__('profile-filament::pages/security.passkeys.actions.add.name_field_placeholder'))
            ->required()
            ->maxlength(255)
            ->unique(
                table: config('profile-filament.table_names.webauthn_key'),
                modifyRuleUsing: function (Unique $rule) {
                    $rule->where('user_id', $this->user->id);
                },
            )
            ->id('passkey-register-name')
            ->autofocus()
            ->autocomplete('off')
            ->hidden(fn (): bool => filled($this->upgrading))
            ->extraInputAttributes([
                'x-on:keydown.enter.prevent.stop' => 'submit',
            ]);
    }

    protected function getAttestation(mixed $excludeId = null): array
    {
        if (session()->has(MfaSession::PasskeyAttestationPk->value)) {
            return unserialize(
                session()->get(MfaSession::PasskeyAttestationPk->value),
            )->jsonSerialize();
        }

        $model = config('profile-filament.models.webauthn_key');
        $publicKey = Webauthn::passkeyAttestationObjectFor(
            username: app($model)::getUsername($this->user),
            userId: app($model)::getUserHandle($this->user),
            excludeCredentials: filled($excludeId) ? Arr::wrap($excludeId) : [],
        );

        session()->put(MfaSession::PasskeyAttestationPk->value, serialize($publicKey));

        return $publicKey->jsonSerialize();
    }

    protected function view(): string
    {
        return 'profile-filament::livewire.passkey-manager';
    }
}
