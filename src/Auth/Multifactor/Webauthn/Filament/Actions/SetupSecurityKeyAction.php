<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Filament\Actions;

use Closure;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Concerns\SetsPreferredMultiFactorProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Concerns\ShowsRecoveryCodesAfterAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts\HasWebauthn;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\WebauthnProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Actions\Concerns\RequiresSudoChallenge;
use Rawilk\ProfileFilament\Enums\ProfileFilamentIcon;
use Rawilk\ProfileFilament\Filament\Schemas\Forms\Inputs\Webauthn\SecurityKeyNameInput;
use Throwable;

class SetupSecurityKeyAction extends Action
{
    use RequiresSudoChallenge;
    use SetsPreferredMultiFactorProvider;
    use ShowsRecoveryCodesAfterAction;

    protected null|Closure|WebauthnProvider $provider = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSudoChallenge();

        $this->registerShowsRecoveryCodes();

        $this->color('primary');

        $this->size(Size::Small);

        $this->closeModalByClickingAway(false);
        $this->closeModalByEscaping(false);

        $this->rateLimit(5);

        $this->modalWidth(Width::Large);
        $this->modalHeading(__('profile-filament::auth/multi-factor/webauthn/actions/set-up.modal.heading'));
        $this->modalDescription(__('profile-filament::auth/multi-factor/webauthn/actions/set-up.modal.description'));
        $this->modalIcon(ProfileFilamentIcon::MfaWebauthn->resolve());
        $this->modalIconColor('primary');

        $this->modalSubmitAction(false);
        $this->modalCancelAction(false);

        $this->schema([
            SecurityKeyNameInput::make('name')
                ->extraInputAttributes([
                    // Writing it like this because $wire.mountAction(...) isn't doing anything
                    'x-on:keydown.enter.prevent.stop' => <<<'JS'
                    const form = $el.closest('form');
                    if (! form) {
                        return;
                    }

                    const button = form.querySelector('.webauthn-register');
                    button && button.click();
                    JS,
                ], merge: true),

            View::make('profile-filament::partials.multi-factor.webauthn.register')
                ->viewData(fn (HasActions $livewire) => [
                    'livewireId' => $livewire->getId(),
                ]),

            Action::make('register')
                ->label(__('profile-filament::auth/multi-factor/webauthn/actions/set-up.modal.actions.register.label'))
                ->extraAttributes([
                    'class' => 'webauthn-register',
                ])
                ->action(function (Action $action, HasActions $livewire) {
                    $rateLimitingKey = 'pf-set-up-webauthn:' . $this->getUser()->getAuthIdentifier();

                    if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
                        $this->getRateLimitedNotification(
                            new TooManyRequestsException(
                                static::class,
                                'register',
                                request()->ip(),
                                RateLimiter::availableIn($rateLimitingKey),
                            )
                        )?->send();

                        return;
                    }

                    RateLimiter::hit($rateLimitingKey);

                    $action->getSchemaContainer()->validate();

                    $livewire->dispatch('webauthnRegistrationReady', [
                        'webauthnOptions' => json_decode($this->getProvider()->generateRegistrationOptions()),
                    ]);
                }),
        ]);

        $this->action(function (HasActions $livewire, array $arguments, array $data) {
            if ($this->shouldChallengeForSudo()) {
                $this->cancel();
            }

            $securityKeyJson = data_get($arguments, 'securityKey');
            if (blank($securityKeyJson)) {
                $this->halt();
            }

            DB::transaction(function () use ($securityKeyJson, $data, $livewire): void {
                try {
                    $this->getProvider()->storeSecurityKey(
                        $securityKeyJson,
                        $data,
                        request()->getHost(),
                    );
                } catch (Throwable) {
                    throw ValidationException::withMessages([
                        'mountedActions.0.data.name' => __('profile-filament::auth/multi-factor/webauthn/actions/set-up.messages.failed'),
                    ]);
                }

                $user = Filament::auth()->user();

                $this->setPreferredMultiFactorProvider($user, $this->getProvider()->getId());

                $this->setUpRecoveryCodesIfNeeded($livewire, $user);
            });

            Notification::make()
                ->success()
                ->title(__('profile-filament::auth/multi-factor/webauthn/actions/set-up.notifications.enabled.title'))
                ->icon(Heroicon::OutlinedLockClosed)
                ->send();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'setupSecurityKey';
    }

    public function provider(Closure|WebauthnProvider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): WebauthnProvider
    {
        $provider = $this->evaluate($this->provider);

        if (! ($provider instanceof WebauthnProvider)) {
            throw new LogicException('An instance of [' . WebauthnProvider::class . '] is required for the delete security key action.');
        }

        return $provider;
    }

    protected function getUser(): Authenticatable&HasWebauthn
    {
        /** @var Authenticatable&HasWebauthn $user */
        $user = Filament::auth()->user();

        return $user;
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->danger()
            ->title(__('profile-filament::auth/multi-factor/webauthn/actions/set-up.messages.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(
                array_key_exists('body', __('profile-filament::auth/multi-factor/webauthn/actions/set-up.messages.throttled'))
                    ? __('profile-filament::auth/multi-factor/webauthn/actions/set-up.messages.throttled.body', [
                        'seconds' => $exception->secondsUntilAvailable,
                        'minutes' => $exception->minutesUntilAvailable,
                    ])
                    : null
            );
    }
}
