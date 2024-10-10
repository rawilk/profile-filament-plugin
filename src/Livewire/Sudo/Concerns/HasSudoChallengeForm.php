<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Livewire\Sudo\Concerns;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Timebox;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rawilk\FilamentPasswordInput\Password;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Throwable;

/**
 * @property-read Collection<int, array> $alternateChallengeOptions
 * @property-read array<int, SudoChallengeMode> $challengeOptions
 * @property-read null|SudoChallengeMode $challengeMode
 * @property-read Authenticatable $user
 * @property-read bool $userHasPasskeys
 * @property Form $form
 */
trait HasSudoChallengeForm
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
     * selected mode.
     */
    #[Computed]
    public function alternateChallengeOptions(): Collection
    {
        return collect($this->challengeOptions)
            ->reject(fn (SudoChallengeMode $mode) => $mode === $this->challengeMode)
            ->map(function (SudoChallengeMode $mode): array {
                return [
                    'mode' => $mode,
                    'label' => $mode->linkLabel($this->user),
                ];
            });
    }

    #[Computed]
    public function challengeMode(): ?SudoChallengeMode
    {
        if (! $this->mode) {
            return null;
        }

        return SudoChallengeMode::tryFrom($this->mode);
    }

    #[Computed]
    public function challengeOptions(): array
    {
        $options = [];

        if (Mfa::canUseAuthenticatorAppsForChallenge($this->user)) {
            $options[] = SudoChallengeMode::App;
        }

        // Passkeys or security key
        if (Mfa::canUseWebauthnForChallenge($this->user)) {
            $options[] = SudoChallengeMode::Webauthn;
        }

        if (filled($this->user->getAuthPassword())) {
            $options[] = SudoChallengeMode::Password;
        }

        return $options;
    }

    #[Computed]
    public function user(): Authenticatable
    {
        return filament()->auth()->user();
    }

    #[Computed]
    public function userHasPasskeys(): bool
    {
        return $this->user->hasPasskeys();
    }

    public function mountHasSudoChallengeForm(): void
    {
        $this->form->fill();
    }

    public function submitAction(): Action
    {
        return Action::make('submit')
            ->action('confirm')
            ->color('primary')
            ->label(
                fn () => $this->challengeMode?->actionButton($this->user) ?? __('profile-filament::messages.sudo_challenge.password.submit')
            )
            ->extraAttributes(['class' => 'w-full']);
    }

    public function startWebauthnAction(): Action
    {
        return Action::make('startWebauthn')
            ->label(function (): string {
                if ($this->hasWebauthnError) {
                    return $this->userHasPasskeys
                        ? __('profile-filament::messages.sudo_challenge.webauthn.retry_including_passkeys')
                        : __('profile-filament::messages.sudo_challenge.webauthn.retry');
                }

                return $this->userHasPasskeys
                    ? __('profile-filament::messages.sudo_challenge.webauthn.submit_including_passkeys')
                    : __('profile-filament::messages.sudo_challenge.webauthn.submit');
            })
            ->color('primary')
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

        unset($this->challengeMode, $this->challengeOptions, $this->alternateChallengeOptions);
    }

    protected function userHandle(): ?string
    {
        return $this->user->email;
    }

    protected function passwordSchema(): array
    {
        return [
            Password::make('password')
                ->id("{$this->getId()}.password")
                ->label(__('profile-filament::messages.sudo_challenge.password.input_label'))
                ->required()
                ->hint(
                    filament()->hasPasswordReset()
                        ? new HtmlString(Blade::render(<<<'HTML'
                        <x-filament::link :href="filament()->getRequestPasswordResetUrl()">
                            {{ __('filament-panels::pages/auth/login.actions.request_password_reset.label') }}
                        </x-filament::link>
                        HTML))
                        : null,
                )
                ->extraAlpineAttributes([
                    'x-on:keydown.enter.stop.prevent' => '$wire.confirm',
                ]),
        ];
    }

    protected function authenticatorAppSchema(): array
    {
        return [
            Forms\Components\TextInput::make('totp')
                ->hiddenLabel()
                ->id("{$this->getId()}.totp")
                ->placeholder(__('profile-filament::messages.sudo_challenge.totp.placeholder'))
                ->helperText(__('profile-filament::messages.sudo_challenge.totp.help_text'))
                ->required()
                ->extraAlpineAttributes([
                    'x-on:keydown.enter.stop.prevent' => '$wire.confirm',
                ]),
        ];
    }

    protected function webauthnOptionsUrl(): string
    {
        return URL::temporarySignedRoute(
            name: 'profile-filament::webauthn.assertion_pk',
            expiration: now()->addHour(),
            parameters: [
                'user' => $this->user->getRouteKey(),
                's' => SudoSession::WebauthnAssertionPk->value,
            ],
        );
    }

    protected function confirmIdentity(?array $assertion = null): void
    {
        $this->error = null;
        $this->hasWebauthnError = false;

        App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($assertion) {
            switch ($this->challengeMode) {
                case SudoChallengeMode::App:
                    $data = $this->form->getState();

                    if (! Mfa::usingChallengedUser($this->user)->isValidTotpCode($data['totp'])) {
                        $this->error = __('profile-filament::messages.sudo_challenge.totp.invalid');

                        throw new Halt;
                    }

                    break;

                case SudoChallengeMode::Password:
                    $data = $this->form->getState();

                    if (! Hash::check($data['password'], $this->user->getAuthPassword())) {
                        $this->error = __('profile-filament::messages.sudo_challenge.password.invalid');

                        throw new Halt;
                    }

                    break;

                case SudoChallengeMode::Webauthn:
                    try {
                        Webauthn::verifyAssertion(
                            user: $this->user,
                            assertionResponse: $assertion,
                            storedPublicKey: session()->pull(SudoSession::WebauthnAssertionPk->value),
                        );
                    } catch (Throwable) {
                        $this->error = __('profile-filament::messages.sudo_challenge.webauthn.invalid');
                        $this->hasWebauthnError = true;

                        throw new Halt;
                    }

                    break;

                default:
                    throw new Halt;
            }

            $timebox->returnEarly();
        }, microseconds: 300 * 1000);
    }
}
