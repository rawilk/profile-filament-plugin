<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\GenerateSecurityKeyAuthenticationOptionsAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Auth\Sudo\Contracts\SudoChallengeProvider;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;
use Rawilk\ProfileFilament\Support\Config;

/**
 * @property-read null|ProfileFilamentPlugin $plugin
 * @property-read null|MultiFactorAuthenticationProvider $providerInstance
 * @property-read UserProvider $userProvider
 * @property-read Authenticatable $user
 */
class CrossDomainWebauthnAuth extends SimplePage
{
    #[Locked]
    public ?string $userId = null;

    #[Locked]
    public string $providerId;

    #[Locked]
    public string $origin;

    #[Locked]
    public bool $passkeysOnly = false;

    #[Locked]
    public string $nonce;

    #[Locked]
    public bool $isSudo = false;

    #[Computed]
    public function plugin(): ?ProfileFilamentPlugin
    {
        return ProfileFilament::plugin();
    }

    #[Computed]
    public function providerInstance(): MultiFactorAuthenticationProvider|SudoChallengeProvider|null
    {
        if ($this->isSudo) {
            return $this->plugin?->getSudoChallengeProvider($this->providerId);
        }

        return $this->plugin?->getMultiFactorAuthenticationProvider($this->providerId);
    }

    #[Computed]
    public function user(): ?Authenticatable
    {
        return $this->userProvider->retrieveById(
            Crypt::decrypt($this->userId)
        );
    }

    #[Computed]
    public function userProvider(): UserProvider
    {
        return Filament::auth()->getProvider() ?? auth()->guard('web')->getProvider();
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::auth/multi-factor/webauthn/actions/auth-on-domain.title');
    }

    public function mount(Request $request): void
    {
        $this->userId = $request->query('user');
        $this->providerId = $request->query('providerId');
        $this->origin = $request->query('origin');
        $this->passkeysOnly = (int) $request->query('passkey') !== 0;
        $this->nonce = $request->query('nonce');

        if (! $this->providerInstance) {
            throw new LogicException('A multi-factor authentication provider instance was not resolved.');
        }

        if ($request->query('sudo')) {
            $this->isSudo = true;
        }

        $this->promptAuthenticationFromUser();
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
            ->id('form')
            ->livewireSubmitHandler('authenticate');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(function (): array {
                return [
                    Text::make(__('profile-filament::auth/multi-factor/webauthn/actions/auth-on-domain.form.messages.prompt'))
                        ->color('neutral')
                        ->size(Size::Medium)
                        ->extraAttributes([
                            'class' => 'text-center text-pretty',
                        ]),

                    View::make('profile-filament::partials.multi-factor.webauthn.cross-domain-auth')
                        ->viewData(fn () => [
                            'failedText' => __('profile-filament::auth/multi-factor/webauthn/provider.challenge-form.messages.failed'),
                            'livewireId' => $this->getId(),
                        ]),

                    Actions::make([
                        Action::make('authenticate')
                            ->label(__('profile-filament::auth/multi-factor/webauthn/actions/auth-on-domain.form.actions.authenticate.label'))
                            ->action(function () {
                                $this->promptAuthenticationFromUser();
                            }),
                    ])->fullWidth(),
                ];
            });
    }

    public function authenticate(array $arguments): void
    {
        if ($this->isRateLimited($this->user)) {
            return;
        }

        $origin = Js::from(
            Uri::of("https://{$this->origin}")->value()
        );

        $authenticationResponse = data_get($arguments, 'authenticationResponse');

        $nonce = Js::from($this->nonce);

        // We'll defer authentication to our passkey authentication controller.
        if ($this->passkeysOnly) {
            // We will need to re-push the options to the session since we're on a different domain session.
            $options = Js::from(
                Crypt::encrypt(WebauthnSession::AuthenticationOptions->pull())
            );

            $this->js(<<<JS
            if (! window.opener) {
                return;
            }

            window.opener.postMessage({
                type: 'webauthn-external-auth-success',
                authenticationResponse: {$authenticationResponse},
                options: {$options},
                nonce: {$nonce},
            }, {$origin});
            JS);

            return;
        }

        if ($this->providerInstance->isValidSecurityKeyChallenge($authenticationResponse, request(), $this->user)) {
            $userId = Js::from($this->userId);
            $challenge = Js::from(
                Hash::make($this->getChallengeForOrigin())
            );

            $this->js(<<<JS
            if (! window.opener) {
                return;
            }

            window.opener.postMessage({
                type: 'webauthn-external-auth-success',
                userId: {$userId},
                challenge: {$challenge},
                nonce: {$nonce},
            }, {$origin});
            JS);

            return;
        }

        $this->dispatch('webauthnAuthenticationFailed', [
            'message' => __('profile-filament::auth/multi-factor/webauthn/provider.challenge-form.messages.failed'),
        ]);
    }

    protected function promptAuthenticationFromUser(): void
    {
        $action = Config::getWebauthnAction(
            'generate_security_key_authentication_options',
            GenerateSecurityKeyAuthenticationOptionsAction::class,
        );

        $options = $action(
            isPasskey: $this->passkeysOnly,
            user: $this->passkeysOnly ? null : $this->user,
        );

        $this->dispatch('webauthnAuthenticationReady', [
            'webauthnOptions' => json_decode($options),
        ]);
    }

    /**
     * Generate a challenge we can verify on the origin.
     */
    protected function getChallengeForOrigin(): string
    {
        $challenge = Str::random(32);

        cache()->put('mfa.external-challenge:' . $this->user->getKey(), $challenge, now()->addSeconds(30));

        return $challenge;
    }

    protected function isRateLimited(Authenticatable $user): bool
    {
        $rateLimitKey = "pf-cross-domain-webauthn-auth:{$user->getAuthIdentifier()}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 5)) {
            $this->getRateLimitedNotification(
                new TooManyRequestsException(
                    static::class,
                    'authenticate',
                    request()->ip(),
                    RateLimiter::availableIn($rateLimitKey),
                )
            )?->send();

            return true;
        }

        RateLimiter::hit($rateLimitKey);

        return false;
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::auth/pages/login.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('filament-panels::auth/pages/login.notifications.throttled') ?: []) ? __('filament-panels::auth/pages/login.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }
}
