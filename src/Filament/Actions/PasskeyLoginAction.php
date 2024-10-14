<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Actions;

use Closure;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Timebox;
use Rawilk\ProfileFilament\Actions\Auth\PrepareUserSession;
use Rawilk\ProfileFilament\Dto\Auth\TwoFactorLoginEventBag;
use Rawilk\ProfileFilament\Enums\Livewire\MfaChallengeMode;
use Rawilk\ProfileFilament\Enums\Session\MfaSession;
use Rawilk\ProfileFilament\Exceptions\Webauthn\AssertionFailed;
use Rawilk\ProfileFilament\Facades\Webauthn;
use Throwable;

class PasskeyLoginAction extends Action
{
    use WithRateLimiting;

    protected ?string $error = null;

    protected ?array $pipes = null;

    protected ?Closure $authenticateUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name('passkeyLogin');

        $this->alpineClickHandler('login');

        $this->defaultView('profile-filament::filament.actions.passkey-login');

        $this->color('gray');

        $this->label(__('profile-filament::pages/mfa.webauthn.passkey_login_button'));

        $this->failureNotification(
            fn (): Notification => Notification::make()
                ->danger()
                ->title(__('profile-filament::pages/mfa.webauthn.assert.failure_title'))
                ->body($this->error ?? __('profile-filament::pages/mfa.webauthn.assert.failure'))
                ->persistent()
        );

        $this->action(function (array $arguments, Request $request) {
            try {
                $this->rateLimit(5);
            } catch (TooManyRequestsException $exception) {
                $this->getRateLimitedNotification($exception)?->send();

                $this->cancel();
            }

            if (! $assertion = data_get($arguments, 'assertion')) {
                $this->cancel();
            }

            $response = App::make(Timebox::class)->call(callback: function (Timebox $timebox) use ($assertion) {
                try {
                    $response = Webauthn::verifyAssertion(
                        user: null,
                        assertionResponse: $assertion,
                        storedPublicKey: session()->pull(MfaSession::PasskeyAssertionPk->value),
                        requiresPasskey: true,
                    );
                } catch (Throwable|AssertionFailed $e) {
                    if ($e instanceof AssertionFailed) {
                        $this->error = $e->getMessage();
                    }

                    return null;
                }

                $timebox->returnEarly();

                return $response;
            }, microseconds: 300 * 1000);

            if (! $response) {
                $this->failure();

                $this->cancel();
            }

            if (is_callable($this->authenticateUsing)) {
                return $this->evaluate($this->authenticateUsing, [
                    'passkey' => $response['authenticator'],
                    'publicKeyCredentialSource' => $response['publicKeyCredentialSource'],
                    'request' => $request,
                ]);
            }

            /** @var \Rawilk\ProfileFilament\Models\WebauthnKey $authenticator */
            $authenticator = $response['authenticator'];

            $eventBag = new TwoFactorLoginEventBag(
                user: $authenticator->user,
                remember: true,
                data: [],
                request: $request,
                mfaChallengeMode: MfaChallengeMode::Webauthn,
                assertionResponse: $assertion,
            );

            return app(Pipeline::class)
                ->send($eventBag)
                ->through($this->getAuthenticationPipes())
                ->then(fn () => app(LoginResponse::class));
        });
    }

    public function authenticateUsing(?Closure $callback = null): static
    {
        $this->authenticateUsing = $callback;

        return $this;
    }

    public function pipeThrough(array $pipes): static
    {
        $this->pipes = $pipes;

        return $this;
    }

    public function getLivewireTarget(): ?string
    {
        return 'mountAction';
    }

    public function passkeyOptionsUrl(): string
    {
        return URL::temporarySignedRoute(
            name: 'profile-filament::webauthn.passkey_assertion_pk',
            expiration: now()->addHour(),
        );
    }

    protected function getAuthenticationPipes(): array
    {
        if (is_array($this->pipes)) {
            return $this->pipes;
        }

        return [PrepareUserSession::class];
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): Notification
    {
        return Notification::make()
            ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => ceil($exception->secondsUntilAvailable / 60),
            ]))
            ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => ceil($exception->secondsUntilAvailable / 60),
            ]) : null)
            ->danger();
    }
}
