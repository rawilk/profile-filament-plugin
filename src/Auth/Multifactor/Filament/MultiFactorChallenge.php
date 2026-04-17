<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Filament;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\Dto\MultiFactorEventBagContract;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\RecoveryProvider;
use Rawilk\ProfileFilament\Facades\Mfa;
use Rawilk\ProfileFilament\Facades\ProfileFilament;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read null|MultiFactorAuthenticationProvider|RecoveryProvider $currentProviderInstance
 * @property-read Schema $form
 * @property-read ProfileFilamentPlugin $plugin
 * @property-read Authenticatable|null $user
 */
class MultiFactorChallenge extends SimplePage
{
    protected const string RECOVERY_ID = '_recovery_';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    #[Locked]
    public ?string $currentProvider = null;

    #[Computed]
    public function user(): ?Authenticatable
    {
        return $this->getUser();
    }

    #[Computed]
    public function plugin(): ProfileFilamentPlugin
    {
        return filament(ProfileFilamentPlugin::PLUGIN_ID);
    }

    #[Computed]
    public function currentProviderInstance(): null|MultiFactorAuthenticationProvider|RecoveryProvider
    {
        if ($this->currentProvider === null) {
            return null;
        }

        if ($this->currentProvider === static::RECOVERY_ID) {
            return $this->getRecoveryProvider($this->user);
        }

        return $this->plugin->getMultiFactorAuthenticationProvider($this->currentProvider);
    }

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        if (! $this->user) {
            redirect()->to(Filament::getLoginUrl());

            return;
        }

        if (Mfa::passwordConfirmationHasExpired()) {
            $this->getPasswordConfirmationExpiredNotification()?->send();

            redirect()->to(Filament::getLoginUrl());

            return;
        }

        $this->currentProvider = ProfileFilament::preferredMfaProviderFor(
            user: $this->user,
            enabledProviders: collect($this->plugin->getMultiFactorAuthenticationProviders())
                ->filter(fn (MultiFactorAuthenticationProvider $provider): bool => $provider->isEnabled($this->user))
        );

        $this->form->fill();

        if ($this->currentProviderInstance && $this->currentProviderInstance instanceof HasBeforeChallengeHook) {
            $this->currentProviderInstance->beforeChallenge($this->user);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return __('profile-filament::auth/multi-factor/challenge/challenge.title');
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('profile-filament::auth/multi-factor/challenge/challenge.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->currentProvider === null) {
            return null;
        }

        return __('profile-filament::auth/multi-factor/challenge/challenge.subheading');
    }

    public function authenticate(): LoginResponse|Responsable|null
    {
        if (! $this->user) {
            redirect()->to(Filament::getLoginUrl());

            return null;
        }

        if ($this->isRateLimited($this->user)) {
            return null;
        }

        $eventBag = app(MultiFactorEventBagContract::class)
            ->setData($this->form->getState())
            ->setRequest(request())
            ->setRemember(Mfa::remember())
            ->setUser($this->user);

        return Pipeline::send($eventBag)
            ->through($this->plugin->getMultiFactorChallengePipes($this->user))
            ->then(fn () => app(LoginResponse::class));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(function (): array {
                $user = $this->user;

                if (blank($user)) {
                    return [];
                }

                $enabledMultiFactorProviders = collect($this->plugin->getMultiFactorAuthenticationProviders())
                    ->filter(fn (MultiFactorAuthenticationProvider $provider): bool => $provider->isEnabled($user));

                $recoveryProvider = $this->getRecoveryProvider($user);
                $recoveryProviderComponents = $recoveryProvider
                    ? Group::make($recoveryProvider->getChallengeFormComponents($user))
                        ->visible(fn (): bool => $this->currentProvider === static::RECOVERY_ID)
                        ->statePath(static::RECOVERY_ID)
                    : null;

                return [
                    Text::make(fn (\Livewire\Component $livewire) => $livewire->getErrorBag()->first('multiFactorError'))
                        ->color('danger')
                        ->visible(fn (\Livewire\Component $livewire) => $livewire->getErrorBag()->has('multiFactorError')),

                    ...Arr::wrap($this->getProviderOptionsComponent($enabledMultiFactorProviders, $recoveryProvider)),

                    ...$enabledMultiFactorProviders
                        ->map(
                            fn (MultiFactorAuthenticationProvider $provider): Component => Group::make($provider->getChallengeFormComponents($user))
                                ->statePath($provider->getId())
                                ->when(
                                    $enabledMultiFactorProviders->isNotEmpty(),
                                    fn (Group $group) => $group->visible(
                                        fn (): bool => $this->currentProvider === $provider->getId(),
                                    ),
                                ),
                        )
                        ->all(),

                    ...Arr::wrap($recoveryProviderComponents),
                ];
            })
            ->statePath('data');
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
            ->livewireSubmitHandler('authenticate')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),

                ...Arr::wrap($this->getChangeProviderAction()),
            ]);
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    protected function getUser(): ?Authenticatable
    {
        return Mfa::challengedUser();
    }

    /**
     * @return array<Action|\Filament\Actions\ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getProviderOptionsComponent(Collection $enabledMultiFactorProviders, ?RecoveryProvider $recoveryProvider): ?Component
    {
        // MultiFactor Providers and Recovery Providers expose the same methods we need here, so we can just combine them.
        $providers = (clone $enabledMultiFactorProviders)->push($recoveryProvider)->filter();

        if ($providers->count() <= 1) {
            return null;
        }

        return Section::make(__('profile-filament::auth/multi-factor/challenge/challenge.form.provider.heading'))
            ->compact()
            ->secondary()
            ->schema(
                fn (Section $section): array => [
                    Actions::make(
                        $providers->map(
                            fn (MultiFactorAuthenticationProvider|RecoveryProvider $provider): Action => Action::make(
                                'changeProvider.' . ($provider instanceof MultiFactorAuthenticationProvider ? $provider->getId() : static::RECOVERY_ID)
                            )
                                ->color('gray')
                                ->label($provider->getChangeToProviderActionLabel($this->user))
                                ->extraAttributes(['class' => 'w-full'])
                                ->action(function () use ($provider) {
                                    $id = $provider instanceof MultiFactorAuthenticationProvider ? $provider->getId() : static::RECOVERY_ID;

                                    if ($id === static::RECOVERY_ID && (! $this->plugin->isMultiFactorRecoverable())) {
                                        return;
                                    }

                                    $this->currentProvider = $id;
                                    unset($this->currentProviderInstance);
                                })
                                ->after(function () use ($provider, $section) {
                                    if ($this->currentProvider === null) {
                                        return;
                                    }

                                    $id = $provider instanceof MultiFactorAuthenticationProvider ? $provider->getId() : static::RECOVERY_ID;

                                    $section
                                        ->getContainer()
                                        ->getComponent($id)
                                        ->getChildSchema()
                                        ->fill();

                                    if (! ($provider instanceof HasBeforeChallengeHook)) {
                                        return;
                                    }

                                    $provider->beforeChallenge($this->user);
                                })
                        )->all()
                    ),
                ]
            )
            ->visible(fn (): bool => $this->currentProvider === null);
    }

    protected function getChangeProviderAction(): ?Actions
    {
        $user = $this->user;

        if (blank($user)) {
            return null;
        }

        $enabledMultiFactorProviders = collect($this->plugin->getMultiFactorAuthenticationProviders())
            ->filter(fn (MultiFactorAuthenticationProvider $provider): bool => $provider->isEnabled($user));

        $recoveryProvider = $this->getRecoveryProvider($user);

        $providers = $enabledMultiFactorProviders->push($recoveryProvider)->filter();

        // We don't need to show an action to change the mfa provider if there is one or less available.
        if ($providers->count() <= 1) {
            return null;
        }

        return Actions::make([
            Action::make('changeProvider')
                ->label(__('profile-filament::auth/multi-factor/challenge/challenge.actions.change-provider.label'))
                ->link()
                ->action(function () use ($providers) {
                    if ($providers->count() <= 1) {
                        return;
                    }

                    $this->currentProvider = null;
                    unset($this->currentProviderInstance);
                })
                ->hidden(fn (): bool => $this->currentProvider === null),
        ])
            ->fullWidth($this->hasFullWidthFormActions())
            ->alignment($this->getFormActionsAlignment())
            ->key('change-provider-actions');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(fn (): ?string => $this->currentProviderInstance?->getChallengeSubmitLabel())
            ->visible(fn (): bool => $this->currentProviderInstance !== null && filled($this->currentProviderInstance->getChallengeSubmitLabel()))
            ->submit('authenticate');
    }

    protected function getPasswordConfirmationExpiredNotification(): ?Notification
    {
        return Notification::make()
            ->danger()
            ->title(__('profile-filament::auth/multi-factor/challenge/challenge.messages.password-confirmation-expired'));
    }

    /**
     * @param  Authenticatable&\Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\HasMultiFactorAuthenticationRecovery  $user
     */
    protected function getRecoveryProvider(Authenticatable $user): ?RecoveryProvider
    {
        if (! $this->plugin->isMultiFactorRecoverable()) {
            return null;
        }

        $recoveryProvider = $this->plugin->getMultiFactorRecoveryProvider();

        return $recoveryProvider?->isEnabled($user) ? $recoveryProvider : null;
    }

    protected function isRateLimited(Authenticatable $user): bool
    {
        $rateLimitKey = "pf-multi-factor-challenge:{$user->getAuthIdentifier()}";

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
