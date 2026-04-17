<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Concerns;

use Closure;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Enums\EmailCodeSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\Notifications\VerifyEmailAuthenticationNotification;
use SensitiveParameter;
use Valorin\Random\Random;

use function Illuminate\Support\defer;

trait VerifiesEmailAuthentication
{
    protected int $codeExpiryMinutes = 15;

    protected ?Closure $generateCodesUsingCallback = null;

    protected string $codeNotification = VerifyEmailAuthenticationNotification::class;

    public function beforeChallenge(Authenticatable $user): void
    {
        if (! ($user instanceof HasEmailAuthentication)) {
            throw new LogicException('The user model must implement the [' . HasEmailAuthentication::class . '] interface to use email authentication.');
        }

        $this->sendCode($user);
    }

    public function isEnabled(Authenticatable $user): bool
    {
        if (! ($user instanceof HasEmailAuthentication)) {
            throw new LogicException('The user model must implement the [' . HasEmailAuthentication::class . '] interface to use email authentication.');
        }

        return $user->hasEmailAuthentication();
    }

    public function verifyCode(#[SensitiveParameter] string $code): bool
    {
        $codeHash = EmailCodeSession::Code->get();

        /** @var \Carbon\CarbonInterface|null $codeExpiresAt */
        $codeExpiresAt = EmailCodeSession::ExpiresAt->get();

        if (
            blank($codeHash)
            || blank($codeExpiresAt)
            || (! Hash::check($code, $codeHash))
            || $codeExpiresAt->isPast()
        ) {
            return false;
        }

        EmailCodeSession::Code->forget();
        EmailCodeSession::ExpiresAt->forget();

        return true;
    }

    public function sendCode(HasEmailAuthentication $user): bool
    {
        if (! ($user instanceof Model)) {
            throw new LogicException('The [' . $user::class . '] class must be an instance of [' . Model::class . '] to use email authentication');
        }

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new LogicException("Model [{$userClass}] does not have a [notify()] method.");
        }

        $rateLimitKey = $this->getSendCodeRateLimitKey($user);

        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 2)) {
            return false;
        }

        RateLimiter::hit($rateLimitKey);

        $code = $this->generateCode();
        $codeExpiryMinutes = $this->getCodeExpiryMinutes();

        EmailCodeSession::Code->set(Hash::make($code));
        EmailCodeSession::ExpiresAt->set(now()->addMinutes($codeExpiryMinutes)->unix());

        defer(
            fn () => $user->notify(app($this->getCodeNotification(), [
                'code' => $code,
                'codeExpiryMinutes' => $codeExpiryMinutes,
            ]))
        );

        return true;
    }

    public function getSendCodeRateLimitKey(Model $user): string
    {
        return "pf-email-authentication:{$user->getKey()}";
    }

    public function codeExpiryMinutes(int $minutes): static
    {
        $this->codeExpiryMinutes = $minutes;

        return $this;
    }

    public function getCodeExpiryMinutes(): int
    {
        return $this->codeExpiryMinutes;
    }

    public function generateCodesUsing(?Closure $callback): static
    {
        $this->generateCodesUsingCallback = $callback;

        return $this;
    }

    public function generateCode(): string
    {
        if ($this->generateCodesUsingCallback) {
            return ($this->generateCodesUsingCallback)();
        }

        return Random::otp(length: 6);
    }

    public function notifyWith(string $notification): static
    {
        $this->codeNotification = $notification;

        return $this;
    }

    public function getCodeNotification(): string
    {
        return $this->codeNotification;
    }

    protected function redactEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$handle, $domain] = explode('@', $email, 2);

        return substr($handle, 0, 2) . '****@' . $domain;
    }

    protected function getThrottledNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('profile-filament::auth/multi-factor/email/provider.challenge-form.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(
                array_key_exists('body', __('profile-filament::auth/multi-factor/email/provider.challenge-form.notifications.throttled'))
                    ? __('profile-filament::auth/multi-factor/email/provider.challenge-form.notifications.throttled.body', [
                        'seconds' => $exception->secondsUntilAvailable,
                        'minutes' => $exception->minutesUntilAvailable,
                    ])
                    : null
            )
            ->danger();
    }
}
