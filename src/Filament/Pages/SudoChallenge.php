<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Timebox;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rawilk\FilamentPasswordInput\Password;
use Rawilk\ProfileFilament\Concerns\Sudo\ChallengesSudoMode;
use Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode;
use Rawilk\ProfileFilament\Enums\Session\SudoSession;
use Rawilk\ProfileFilament\Events\Sudo\SudoModeActivated;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\Facades\Sudo;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Throwable;

use function Filament\Support\get_color_css_variables;

/**
 * @property-read \Illuminate\Support\Collection $alternateChallengeOptions
 * @property-read bool $isTotp
 * @property-read bool $isWebauthn
 * @property-read null|string $formIcon
 * @property-read null|string $formLabel
 * @property-read null|\Rawilk\ProfileFilament\Enums\Livewire\SudoChallengeMode $sudoChallengeModeEnum
 * @property-read User $user
 * @property-read \Filament\Forms\Form $form
 */
class SudoChallenge extends SimplePage
{
    use ChallengesSudoMode;
    use InteractsWithFormActions;
    use WithRateLimiting;

    #[Locked]
    public array $challengeOptions = [];

    public array $data = [];

    public bool $hasWebauthnError = false;

    #[Locked]
    public ?string $error = null;

    #[Locked]
    public string $sudoChallengeMode;

    protected static string $view = 'profile-filament::pages.sudo-challenge';

    public static function setLayout(string $layout): void
    {
        static::$layout = $layout;
    }

    #[Computed]
    public function alternateChallengeOptions(): Collection
    {
        return $this->mapAlternateChallengeOptions($this->challengeOptions, $this->sudoChallengeMode, $this->user);
    }

    #[Computed]
    public function isTotp(): bool
    {
        return $this->sudoChallengeModeEnum === SudoChallengeMode::App;
    }

    #[Computed]
    public function isWebauthn(): bool
    {
        return $this->sudoChallengeModeEnum === SudoChallengeMode::Webauthn;
    }

    #[Computed]
    public function formIcon(): ?string
    {
        return $this->getSudoMethodIcon($this->sudoChallengeModeEnum);
    }

    #[Computed]
    public function formLabel(): ?string
    {
        return $this->getSudoMethodLabel($this->sudoChallengeModeEnum);
    }

    #[Computed]
    public function sudoChallengeModeEnum(): ?SudoChallengeMode
    {
        return SudoChallengeMode::tryFrom($this->sudoChallengeMode);
    }

    #[Computed]
    public function user(): User
    {
        return filament()->auth()->user();
    }

    public function authenticate(Request $request, $assertionResponse = null): void
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

        $verified = App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($assertionResponse) {
            $data = $this->isWebauthn ? $assertionResponse : $this->form->getState()['data'];
            if (! $this->verifyChallenge($data)) {
                return false;
            }

            $timebox->returnEarly();

            return true;
        }, microseconds: 300 * 1000);

        if (! $verified) {
            return;
        }

        Sudo::activate();
        SudoModeActivated::dispatch($this->user, $request);

        redirect()->intended(Filament::getHomeUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::messages.sudo_challenge.title');
    }

    public function getHeading(): string|Htmlable
    {
        $heading = __('profile-filament::messages.sudo_challenge.title');
        $colors = get_color_css_variables('primary', [100, 400, 500, 600]);

        return new HtmlString(Blade::render(<<<HTML
        <div class="mb-2 mt-4 flex items-center justify-center">
            <div class="rounded-full fi-color-custom bg-custom-100 dark:bg-custom-500/20 p-3" style="{$colors}">
                <x-filament::icon
                    icon="heroicon-m-finger-print"
                    alias="sudo::challenge"
                    class="fi-modal-icon fi-sudo-challenge-heading-icon h-6 w-6 text-custom-600 dark:text-custom-400"
                />
            </div>
        </div>

        <div>
            {$heading}
        </div>
        HTML));
    }

    public function mount(): void
    {
        if ($this->sudoModeIsActive()) {
            redirect()->intended(Filament::getHomeUrl());

            return;
        }

        $this->challengeOptions = $this->sudoChallengeOptionsFor($this->user);
        $this->sudoChallengeMode = ProfileFilament::preferredSudoChallengeMethodFor($this->user, $this->challengeOptions);

        $this->form->fill();
    }

    public function setMode(string $mode): void
    {
        if (! in_array($mode, $this->challengeOptions, true)) {
            return;
        }

        unset($this->sudoChallengeModeEnum);

        $this->sudoChallengeMode = $mode;
        $this->form->fill();
        $this->error = null;
        $this->hasWebauthnError = false;
    }

    protected function getFormSchema(): array
    {
        return [
            $this->isTotp
                ? $this->getTotpInput()
                : $this->getPasswordInput(),
        ];
    }

    protected function getPasswordInput(): Component
    {
        return Password::make('password')
            ->label(__('profile-filament::messages.sudo_challenge.password.input_label'))
            ->hint(
                filament()->hasPasswordReset()
                    ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()">{{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>'))
                    : null,
            )
            ->required()
            ->autofocus()
            ->statePath('data.password');
    }

    protected function getTotpInput(): Component
    {
        return TextInput::make('code')
            ->hiddenLabel()
            ->placeholder(__('profile-filament::messages.sudo_challenge.totp.placeholder'))
            ->helperText(__('profile-filament::messages.sudo_challenge.totp.help_text'))
            ->required()
            ->autofocus()
            ->statePath('data.totp');
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
            ->label(fn () => $this->getSudoMethodSubmitLabel($this->sudoChallengeModeEnum))
            ->submit('authenticate');
    }

    protected function getWebauthnFormAction(): Action
    {
        return Action::make('webauthn')
            ->livewireClickHandlerEnabled(false)
            ->label(function () {
                if ($this->hasWebauthnError) {
                    /** @phpstan-ignore-next-line */
                    return $this->user->hasPasskeys()
                        ? __('profile-filament::messages.sudo_challenge.webauthn.retry_including_passkeys')
                        : __('profile-filament::messages.sudo_challenge.webauthn.retry');
                }

                /** @phpstan-ignore-next-line */
                return $this->user->hasPasskeys()
                    ? __('profile-filament::messages.sudo_challenge.webauthn.submit_including_passkeys')
                    : __('profile-filament::messages.sudo_challenge.webauthn.submit');
            })
            ->extraAttributes([
                'x-on:click' => 'submit',
            ]);
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    protected function getSudoMethodIcon(string|SudoChallengeMode $mode): ?string
    {
        if (is_string($mode)) {
            return null;
        }

        return $mode->icon();
    }

    protected function getSudoMethodLabel(string|SudoChallengeMode $mode): ?string
    {
        if (is_string($mode)) {
            return $mode;
        }

        return $mode->heading($this->user);
    }

    protected function getSudoMethodSubmitLabel(string|SudoChallengeMode $mode): string
    {
        if (is_string($mode)) {
            return $mode;
        }

        return $mode->actionButton($this->user);
    }

    protected function verifyChallenge(array $data): bool
    {
        switch ($this->sudoChallengeMode) {
            case SudoChallengeMode::App->value:
                if (! Mfa::usingChallengedUser(filament()->auth()->user())->isValidTotpCode($data['totp'] ?? '')) {
                    $this->error = __('profile-filament::messages.sudo_challenge.totp.invalid');

                    return false;
                }

                return true;

            case SudoChallengeMode::Webauthn->value:
                try {
                    Webauthn::verifyAssertion(
                        user: filament()->auth()->user(),
                        assertionResponse: $data,
                        storedPublicKey: unserialize(session()->pull(SudoSession::WebauthnAssertionPk->value)),
                    );
                } catch (Throwable) {
                    $this->error = __('profile-filament::messages.sudo_challenge.webauthn.invalid');

                    return false;
                }

                return true;

            case SudoChallengeMode::Password->value:
                if (! Hash::check($data['password'] ?? '', filament()->auth()->user()->getAuthPassword())) {
                    $this->error = __('profile-filament::messages.sudo_challenge.password.invalid');

                    return false;
                }

                return true;

            default:
                throw new Exception('Sudo challenge mode "' . $this->sudoChallengeMode . '" is not supported by this package.');
        }
    }
}
