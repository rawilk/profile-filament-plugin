<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Pipeline;
use Rawilk\ProfileFilament\Dto\Auth\TwoFactorLoginEventBag;
use Rawilk\ProfileFilament\Enums\Livewire\MfaChallengeMode;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\ProfileFilament;

class MfaChallenge extends SimplePage
{
    use Concerns\ChallengesMfa;
    use WithRateLimiting;

    protected static string $view = 'profile-filament::pages.mfa-challenge';

    public static function setLayout(string $layout): void
    {
        static::$layout = $layout;
    }

    public function mount(): void
    {
        if (! $this->user) {
            redirect()->to(filament()->getLoginUrl());

            return;
        }

        if (Mfa::isConfirmedInSession($this->user)) {
            redirect()->intended(filament()->getHomeUrl());

            return;
        }

        $this->mode = ProfileFilament::preferredMfaMethodFor($this->user, $this->challengeOptions);
    }

    public function getTitle(): string|Htmlable
    {
        return $this->challengeMode?->formHeading() ?? __('profile-filament::pages/mfa.heading');
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema(fn () => match ($this->challengeMode) {
                default => [],
                MfaChallengeMode::App => $this->authenticatorAppSchema(),
                MfaChallengeMode::RecoveryCode => $this->recoveryCodeSchema(),
            });
    }

    public function authenticate(Request $request, ?array $assertion = null)
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getThrottledNotification($exception)?->send();

            return;
        }

        try {
            $this->confirmIdentity($assertion);
        } catch (Halt) {
            return;
        }

        if (filament()->auth()->check()) {
            // We are being enforced by the mfa middleware.
            // This will probably be the case for most apps.
            Mfa::confirmUserSession($this->user);

            redirect()->intended(filament()->getHomeUrl());

            return;
        }

        // If we reached this point, this is probably a custom workflow.
        // We'll send a DTO object containing the authentication info
        // through pipes defined in the package config.
        $dto = new TwoFactorLoginEventBag(
            user: $this->user,
            remember: Mfa::remember(),
            data: $this->form->getState(),
            request: $request,
            mfaChallengeMode: $this->challengeMode,
            assertionResponse: $assertion,
        );

        return Pipeline::send($dto)
            ->through(ProfileFilament::getMfaAuthenticationPipes())
            ->then(fn () => app(LoginResponse::class));
    }

    protected function getThrottledNotification(TooManyRequestsException $exception): ?Notification
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
