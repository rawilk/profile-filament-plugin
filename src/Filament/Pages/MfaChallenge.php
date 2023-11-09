<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Timebox;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Dto\Auth\TwoFactorLoginEventBag;
use Rawilk\ProfileFilament\Enums\Livewire\MfaChallengeMode;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AssertionFailed;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Throwable;

/**
 * @property-read \Illuminate\Support\Collection $alternativeChallengeOptions
 * @property-read string $alternativesHeading
 * @property-read string $formLabel
 * @property-read bool $isTotp
 * @property-read bool $isWebauthn
 * @property-read null|\Rawilk\ProfileFilament\Enums\Livewire\MfaChallengeMode $mfaChallengeMode
 * @property-read string|null $modeIcon
 * @property-read bool $hasWebauthn
 */
class MfaChallenge extends SimplePage
{
    use InteractsWithFormActions;
    use WithRateLimiting;

    #[Locked]
    public string $mode;

    #[Locked]
    public array $challengeOptions;

    public ?string $code;

    public bool $hasWebauthnError = false;

    #[Locked]
    public ?string $error = null;

    protected static string $view = 'profile-filament::pages.mfa-challenge';

    public static function setLayout(string $layout): void
    {
        static::$layout = $layout;
    }

    #[Computed]
    public function isTotp(): bool
    {
        return $this->mfaChallengeMode === MfaChallengeMode::App;
    }

    #[Computed]
    public function isWebauthn(): bool
    {
        return $this->mfaChallengeMode === MfaChallengeMode::Webauthn;
    }

    #[Computed]
    public function mfaChallengeMode(): ?MfaChallengeMode
    {
        return MfaChallengeMode::tryFrom($this->mode);
    }

    #[Computed]
    public function modeIcon(): ?string
    {
        return $this->getMfaModeIcon($this->mfaChallengeMode ?? $this->mode);
    }

    #[Computed]
    public function formLabel(): string
    {
        return $this->getMfaMethodLabel($this->mfaChallengeMode ?? $this->mode);
    }

    #[Computed]
    public function hasWebauthn(): bool
    {
        return in_array(MfaChallengeMode::Webauthn->value, $this->challengeOptions, true);
    }

    #[Computed]
    public function alternativesHeading(): string
    {
        return $this->mfaChallengeMode?->alternativeHeading() ?? __('profile-filament::pages/mfa.alternative_heading');
    }

    #[Computed]
    public function alternativeChallengeOptions(): Collection
    {
        return collect($this->challengeOptions)
            ->filter(fn (string $option) => $option !== $this->mode)
            ->map(function (string $option) {
                $mode = MfaChallengeMode::tryFrom($option) ?? $option;

                return [
                    'key' => $option,
                    'label' => $this->getAltMethodLabel($mode),
                ];
            });
    }

    public function getTitle(): string|Htmlable
    {
        return $this->mfaChallengeMode?->formHeading() ?? __('profile-filament::pages/mfa.heading');
    }

    public function mount(): void
    {
        $user = $this->resolveUser();

        if (! $user) {
            redirect()->to(Filament::getLoginUrl());

            return;
        }

        if (Mfa::isConfirmedInSession($user)) {
            redirect()->intended(Filament::getHomeUrl());

            return;
        }

        $this->challengeOptions = $this->getChallengeOptionsFor($user);
        $this->mode = ProfileFilament::preferredMfaMethodFor($user, $this->challengeOptions);

        $this->form->fill();
    }

    public function setMode(string $mode): void
    {
        if (! in_array($mode, $this->challengeOptions, true)) {
            return;
        }

        unset($this->mfaChallengeMode);

        $this->mode = $mode;
        $this->form->fill();
        $this->error = null;
        $this->hasWebauthnError = false;
    }

    public function authenticate(Request $request, $assertionResponse = null)
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return;
        }

        $user = $this->resolveUser();

        $verified = App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($assertionResponse, $user) {
            if (! $user) {
                return false;
            }

            $data = $this->isWebauthn ? $assertionResponse : $this->form->getState();
            if (! $this->verifyChallenge($data)) {
                return false;
            }

            $timebox->returnEarly();

            return true;
        }, microseconds: 300 * 1000);

        if (! $verified) {
            return;
        }

        if (Filament::auth()->check()) {
            // We are being enforced by the mfa middleware.
            // This will probably be the case for most apps.
            Mfa::confirmUserSession($user);

            redirect()->intended(Filament::getHomeUrl());

            return;
        }

        $eventBag = new TwoFactorLoginEventBag(
            user: Mfa::challengedUser(),
            remember: Mfa::remember(),
            data: $this->form->getState(),
            request: $request,
            mfaChallengeMode: $this->mfaChallengeMode,
            assertionResponse: $assertionResponse,
        );

        return app(Pipeline::class)
            ->send($eventBag)
            ->through(ProfileFilament::getMfaAuthenticationPipes())
            ->then(fn () => app(LoginResponse::class));
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('code')
                ->placeholder($this->isTotp ? __('profile-filament::pages/mfa.totp.placeholder') : __('profile-filament::pages/mfa.recovery_code.placeholder'))
                ->label('')
                ->autocomplete($this->isTotp ? 'one-time-code' : null)
                ->helperText($this->isTotp ? __('profile-filament::pages/mfa.totp.hint') : __('profile-filament::pages/mfa.recovery_code.hint'))
                ->required(fn () => ! $this->isWebauthn)
                ->autofocus(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('profile-filament::pages/mfa.actions.authenticate'))
            ->submit('authenticate');
    }

    protected function getWebauthnFormAction(): Action
    {
        return Action::make('webauthn')
            ->livewireClickHandlerEnabled(false)
            ->label(function () {
                $user = Mfa::challengedUser();

                if ($this->hasWebauthnError) {
                    return $user->hasPasskeys()
                        ? __('profile-filament::pages/mfa.webauthn.retry_including_passkeys')
                        : __('profile-filament::pages/mfa.webauthn.retry');
                }

                return $user->hasPasskeys()
                    ? __('profile-filament::pages/mfa.actions.webauthn_including_passkeys')
                    : __('profile-filament::pages/mfa.actions.webauthn');
            })
            ->extraAttributes([
                'x-on:click' => 'submit',
            ]);
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    protected function getAltMethodLabel(string|MfaChallengeMode $mode): string
    {
        if (is_string($mode)) {
            return $mode;
        }

        return $mode->linkLabel(Mfa::challengedUser());
    }

    protected function getMfaModeIcon(string|MfaChallengeMode $mode): ?string
    {
        if (is_string($mode)) {
            return null;
        }

        return $mode->icon();
    }

    protected function getMfaMethodLabel(string|MfaChallengeMode $mode): string
    {
        if (is_string($mode)) {
            return $mode;
        }

        return $mode->formLabel(Mfa::challengedUser());
    }

    protected function getChallengeOptionsFor(User $user): array
    {
        $options = [];

        if (Mfa::canUseAuthenticatorAppsForChallenge($user)) {
            $options[] = MfaChallengeMode::App->value;
        }

        if (Mfa::canUseWebauthnForChallenge($user)) {
            $options[] = MfaChallengeMode::Webauthn->value;
        }

        $options[] = MfaChallengeMode::RecoveryCode->value;

        return $options;
    }

    protected function resolveUser(): ?User
    {
        if (Filament::auth()->check()) {
            session()->put(MfaSession::User->value, Filament::auth()->id());

            return Filament::auth()->user();
        }

        if (! Mfa::hasChallengedUser()) {
            return null;
        }

        return Mfa::challengedUser();
    }

    protected function verifyChallenge(array $data): bool
    {
        switch ($this->mode) {
            case MfaChallengeMode::App->value:
                if (! Mfa::isValidTotpCode($data['code'])) {
                    $this->addError('code', __('profile-filament::pages/mfa.totp.invalid'));

                    return false;
                }

                return true;

            case MfaChallengeMode::Webauthn->value:
                try {
                    Webauthn::verifyAssertion(
                        user: Mfa::challengedUser(),
                        assertionResponse: $data,
                        storedPublicKey: unserialize(session()->get(MfaSession::AssertionPublicKey->value)),
                    );
                } catch (AssertionFailed|Throwable) {
                    $this->hasWebauthnError = true;
                    $this->error = __('profile-filament::pages/mfa.webauthn.assert.failure');

                    return false;
                }

                session()->forget(MfaSession::AssertionPublicKey->value);

                return true;

            case MfaChallengeMode::RecoveryCode->value:
                if (! Mfa::isValidRecoveryCode($data['code'])) {
                    $this->addError('code', __('profile-filament::pages/mfa.recovery_code.invalid'));

                    return false;
                }

                return true;

            default:
                throw new Exception('Mfa method "' . $this->method . '" is not supported by this package.');
        }
    }
}
