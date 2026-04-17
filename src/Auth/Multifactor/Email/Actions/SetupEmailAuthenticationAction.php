<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Email\Actions;

use Closure;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Email\EmailAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Concerns\ShowsRecoveryCodesAfterAction;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallenge;

class SetupEmailAuthenticationAction extends Action
{
    use RequiresSudoChallenge;
    use ShowsRecoveryCodesAfterAction;

    protected null|Closure|EmailAuthenticationProvider $provider = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSudoChallenge(executeAfterSudo: function () {
            /** @var \Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication $user */
            $user = Filament::auth()->user();

            $this->getProvider()->sendCode($user);
        });

        $this->registerShowsRecoveryCodes();

        $this->label(__('profile-filament::auth/multi-factor/email/actions/set-up.label'));

        $this->size(Size::Small);

        $this->modalHeading(__('profile-filament::auth/multi-factor/email/actions/set-up.modal.heading'));

        $this->modalDescription(__('profile-filament::auth/multi-factor/email/actions/set-up.modal.description'));

        $this->modalWidth(Width::Large);

        $this->modalIcon(Heroicon::OutlinedLockClosed);

        $this->modalIconColor('primary');

        $this->modalSubmitActionLabel(__('profile-filament::auth/multi-factor/email/actions/set-up.modal.actions.submit.label'));

        $this->rateLimit(5);

        $this->schema([
            TextInput::make('code')
                ->label(__('profile-filament::auth/multi-factor/email/actions/set-up.modal.form.code.label'))
                ->validationAttribute(__('profile-filament::auth/multi-factor/email/actions/set-up.modal.form.code.validation-attribute'))
                ->belowContent(
                    Action::make('resend')
                        ->label(__('profile-filament::auth/multi-factor/email/actions/set-up.modal.form.code.actions.resend.label'))
                        ->link()
                        ->action(function (): void {
                            /** @var \Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication|\Illuminate\Database\Eloquent\Model $user */
                            $user = Filament::auth()->user();
                            $provider = $this->getProvider();

                            if (! $provider->sendCode($user)) {
                                $this->getResendThrottledNotification(
                                    new TooManyRequestsException(
                                        static::class,
                                        'resend',
                                        request()->ip(),
                                        RateLimiter::availableIn($provider->getSendCodeRateLimitKey($user)),
                                    )
                                )?->send();

                                return;
                            }

                            Notification::make()
                                ->title(__('profile-filament::auth/multi-factor/email/actions/set-up.modal.form.code.actions.resend.notifications.resent.title'))
                                ->success()
                                ->send();
                        }),
                )
                ->numeric()
                ->autocomplete('one-time-code')
                ->required()
                ->rule(function (): Closure {
                    return function (string $attribute, $value, Closure $fail): void {
                        $rateLimitKey = 'pf-set-up-email-authentication:' . Filament::auth()->id();

                        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 5)) {
                            $fail(__('profile-filament::auth/multi-factor/email/actions/set-up.modal.form.code.messages.rate-limited'));

                            return;
                        }

                        RateLimiter::hit($rateLimitKey);

                        if ($this->getProvider()->verifyCode($value)) {
                            return;
                        }

                        $fail(__('profile-filament::auth/multi-factor/email/actions/set-up.modal.form.code.messages.invalid'));
                    };
                }),
        ]);

        $this->action(function (HasActions $livewire): void {
            if ($this->shouldChallengeForSudo()) {
                $this->cancel();
            }

            /** @var \Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication&\Illuminate\Auth\Authenticatable $user */
            $user = Filament::auth()->user();

            DB::transaction(function () use ($livewire, $user): void {
                $this->getProvider()->enableEmailAuthentication($user);

                $this->setUpRecoveryCodesIfNeeded($livewire, $user);
            });

            Notification::make()
                ->title(__('profile-filament::auth/multi-factor/email/actions/set-up.notifications.enabled.title'))
                ->success()
                ->icon(Heroicon::OutlinedLockClosed)
                ->send();
        });
    }

    public function provider(null|Closure|EmailAuthenticationProvider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): EmailAuthenticationProvider
    {
        $provider = $this->evaluate($this->provider);

        if (! ($provider instanceof EmailAuthenticationProvider)) {
            throw new LogicException('A [' . EmailAuthenticationProvider::class . '] instance must be provided to the [' . static::class . ']');
        }

        return $provider;
    }

    protected function getMountUsingCallback(): ?Closure
    {
        // This runs before sudo mode is checked for, so we need to make sure it's
        // active before we send the code out.
        if ($this->shouldChallengeForSudo()) {
            return null;
        }

        return function () {
            /** @var \Rawilk\ProfileFilament\Auth\Multifactor\Email\Contracts\HasEmailAuthentication $user */
            $user = Filament::auth()->user();

            $this->getProvider()->sendCode($user);
        };
    }

    protected function getResendThrottledNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('profile-filament::auth/multi-factor/email/actions/set-up.modal.form.code.actions.resend.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(
                array_key_exists('body', __('profile-filament::auth/multi-factor/email/actions/set-up.modal.form.code.actions.resend.notifications.throttled'))
                    ? __('profile-filament::auth/multi-factor/email/actions/set-up.modal.form.code.actions.resend.notifications.throttled.body', [
                        'seconds' => $exception->secondsUntilAvailable,
                        'minutes' => $exception->minutesUntilAvailable,
                    ])
                    : null
            )
            ->danger();
    }
}
