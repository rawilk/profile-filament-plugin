<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Timebox;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Enums\Livewire\MfaChallengeMode;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Throwable;

/**
 * @property-read Collection<int, array> $alternateChallengeOptions
 * @property-read null|User $user
 * @property-read null|MfaChallengeMode $challengeMode
 * @property-read array<int, MfaChallengeMode> $challengeOptions
 * @property-read bool $userHasPasskeys
 * @property Forms\Form $form
 */
trait ChallengesMfa
{
    public ?array $data = [];

    #[Locked]
    public ?string $mode = null;

    #[Locked]
    public ?string $error = null;

    #[Locked]
    public bool $hasWebauthnError = false;

    /**
     * If the user has more than one challenge option available,
     * these will be the challenge modes that are not the
     * selected challenge mode.
     */
    #[Computed]
    public function alternateChallengeOptions(): Collection
    {
        return collect($this->challengeOptions)
            ->reject(fn (MfaChallengeMode $mode) => $mode === $this->challengeMode)
            ->map(function (MfaChallengeMode $mode): array {
                return [
                    'mode' => $mode,
                    'label' => $mode->linkLabel($this->user),
                ];
            });
    }

    #[Computed]
    public function challengeMode(): ?MfaChallengeMode
    {
        if (! $this->mode) {
            return null;
        }

        return MfaChallengeMode::tryFrom($this->mode);
    }

    #[Computed]
    public function challengeOptions(): array
    {
        $options = [];

        if (Mfa::canUseAuthenticatorAppsForChallenge($this->user)) {
            $options[] = MfaChallengeMode::App;
        }

        // Passkeys or security key
        if (Mfa::canUseWebauthnForChallenge($this->user)) {
            $options[] = MfaChallengeMode::Webauthn;
        }

        $options[] = MfaChallengeMode::RecoveryCode;

        return $options;
    }

    #[Computed]
    public function user(): ?User
    {
        return $this->resolveUser();
    }

    #[Computed]
    public function userHasPasskeys(): bool
    {
        return $this->user->hasPasskeys();
    }

    public function mountChallengesMfa(): void
    {
        $this->form->fill();
    }

    public function submitAction(): Action
    {
        return Action::make('submit')
            ->action('authenticate')
            ->color('primary')
            ->label(__('profile-filament::pages/mfa.actions.authenticate'))
            ->extraAttributes(['class' => 'w-full']);
    }

    public function startWebauthnAction(): Action
    {
        return Action::make('startWebauthn')
            ->color('primary')
            ->label(function (): string {
                if ($this->hasWebauthnError) {
                    return $this->userHasPasskeys
                        ? __('profile-filament::pages/mfa.webauthn.retry_including_passkeys')
                        : __('profile-filament::pages/mfa.webauthn.retry');
                }

                return $this->userHasPasskeys
                    ? __('profile-filament::pages/mfa.actions.webauthn_including_passkeys')
                    : __('profile-filament::pages/mfa.actions.webauthn');
            })
            ->extraAttributes([
                'class' => 'w-full',
            ])
            ->alpineClickHandler('login');
    }

    public function setChallengeMode(string $mode): void
    {
        $this->form->fill();
        $this->clearValidation();

        $this->mode = $mode;
        $this->error = null;
        $this->hasWebauthnError = false;

        unset($this->challengeOptions, $this->challengeMode, $this->alternateChallengeOptions);
    }

    protected function authenticatorAppSchema(): array
    {
        return [
            Forms\Components\TextInput::make('totp')
                ->label('')
                ->id("{$this->getId()}.totp")
                ->placeholder(__('profile-filament::pages/mfa.totp.placeholder'))
                ->helperText(__('profile-filament::pages/mfa.totp.hint'))
                ->hiddenLabel()
                ->autocomplete('one-time-code')
                ->validationAttribute('code')
                ->required()
                ->autofocus(),
        ];
    }

    protected function recoveryCodeSchema(): array
    {
        return [
            Forms\Components\TextInput::make('code')
                ->label('')
                ->id("{$this->getId()}.code")
                ->placeholder(__('profile-filament::pages/mfa.recovery_code.placeholder'))
                ->helperText(__('profile-filament::pages/mfa.recovery_code.hint'))
                ->hiddenLabel()
                ->validationAttribute('code')
                ->required()
                ->autofocus(),
        ];
    }

    protected function webauthnOptionsUrl(): string
    {
        return URL::temporarySignedRoute(
            name: 'profile-filament::webauthn.assertion_pk',
            expiration: now()->addHour(),
            parameters: [
                'user' => $this->user->getRouteKey(),
                's' => MfaSession::AssertionPublicKey->value,
            ],
        );
    }

    protected function confirmIdentity(?array $assertion = null): void
    {
        $this->error = null;
        $this->hasWebauthnError = false;

        App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($assertion) {
            switch ($this->challengeMode) {
                case MfaChallengeMode::App:
                    $data = $this->form->getState();

                    if (! Mfa::isValidTotpCode($data['totp'])) {
                        $this->error = __('profile-filament::pages/mfa.totp.invalid');

                        throw new Halt;
                    }

                    break;

                case MfaChallengeMode::Webauthn:
                    try {
                        Webauthn::verifyAssertion(
                            user: $this->user,
                            assertionResponse: $assertion,
                            storedPublicKey: session()->pull(MfaSession::AssertionPublicKey->value),
                        );
                    } catch (Throwable) {
                        $this->error = __('profile-filament::pages/mfa.webauthn.assert.failure');
                        $this->hasWebauthnError = true;

                        throw new Halt;
                    }

                    break;

                case MfaChallengeMode::RecoveryCode:
                    $data = $this->form->getState();

                    if (! Mfa::isValidRecoveryCode($data['code'])) {
                        $this->error = __('profile-filament::pages/mfa.recovery_code.invalid');

                        throw new Halt;
                    }

                    break;

                default:
                    throw new Halt;
            }

            $timebox->returnEarly();
        }, microseconds: 300 * 1000);
    }

    protected function resolveUser(): ?User
    {
        if (filament()->auth()->check()) {
            session()->put(MfaSession::User->value, filament()->auth()->id());

            return filament()->auth()->user();
        }

        if (! Mfa::hasChallengedUser()) {
            return null;
        }

        return Mfa::challengedUser();
    }
}
