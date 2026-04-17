<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Sudo\Concerns;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component as FilamentComponent;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\UnorderedList;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Rawilk\ProfileFilament\Auth\Sudo\Contracts\SudoChallengeProvider;
use Rawilk\ProfileFilament\ProfileFilamentPlugin;

/**
 * @property-read ProfileFilamentPlugin $plugin
 * @property-read Authenticatable $user
 * @property-read null|SudoChallengeProvider $currentProviderInstance
 * @property-read Collection $enabledSudoProviders
 * @property Schema $form
 */
trait IssuesSudoChallenge
{
    #[Locked]
    public ?string $currentProvider = null;

    #[Computed]
    public function plugin(): ProfileFilamentPlugin
    {
        return Filament::getCurrentOrDefaultPanel()->getPlugin(ProfileFilamentPlugin::PLUGIN_ID);
    }

    #[Computed]
    public function user(): Authenticatable
    {
        return Filament::auth()->user();
    }

    #[Computed]
    public function currentProviderInstance(): ?SudoChallengeProvider
    {
        if ($this->currentProvider === null) {
            return null;
        }

        return $this->plugin->getSudoChallengeProvider($this->currentProvider);
    }

    #[Computed]
    public function enabledSudoProviders(): Collection
    {
        return collect($this->plugin->getSudoChallengeProviders())
            ->filter(fn (SudoChallengeProvider $provider): bool => $provider->isEnabled($this->user));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(function (): array {
                $enabledSudoProviders = $this->enabledSudoProviders;

                return [
                    ...$enabledSudoProviders->map(
                        fn (SudoChallengeProvider $provider): FilamentComponent => Group::make($provider->getChallengeFormComponents($this->user, authenticateAction: 'authenticate'))
                            ->statePath($provider->getId())
                            ->when(
                                $enabledSudoProviders->isNotEmpty(),
                                fn (Group $group) => $group->visible(
                                    fn (): bool => $this->currentProvider === $provider->getId(),
                                ),
                            ),
                    )
                        ->all(),

                    Actions::make($this->getFormActions())
                        ->key('sudo-form-actions')
                        ->fullWidth()
                        ->visible(fn (): bool => $enabledSudoProviders->isNotEmpty()),
                ];
            })
            ->statePath('data');
    }

    public function alternateOptions(Schema $schema): Schema
    {
        return $schema
            ->components(function (): array {
                $enabledSudoProviders = $this->enabledSudoProviders;

                if ($enabledSudoProviders->count() <= 1) {
                    return [];
                }

                return [
                    UnorderedList::make(
                        fn (): array => $enabledSudoProviders
                            ->filter(fn (SudoChallengeProvider $provider): bool => $provider->getId() !== $this->currentProvider)
                            ->map(
                                fn (SudoChallengeProvider $provider): Action => Action::make('sudoChangeTo.' . $provider->getId())
                                    ->label($provider->getChangeToProviderLabel())
                                    ->size(Size::Small)
                                    ->link()
                                    ->action(function () use ($provider) {
                                        $this->currentProvider = $provider->getId();
                                        unset($this->currentProviderInstance);
                                    })
                                    ->after(function () use ($provider) {
                                        $this->form
                                            ->getComponent($provider->getId())
                                            ->getChildSchema()
                                            ->fill();

                                        if (! ($provider instanceof HasBeforeChallengeHook)) {
                                            return;
                                        }

                                        $provider->beforeChallenge($this->user);
                                    }),
                            )->all(),
                    )
                        ->columns(1)
                        ->size(TextSize::Small)
                        ->extraAttributes([
                            'class' => 'pl-2',
                        ]),
                ];
            });
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
            ->label(fn (): ?string => $this->currentProviderInstance?->getChallengeSubmitLabel())
            ->visible(fn (): bool => $this->currentProviderInstance !== null && filled($this->currentProviderInstance->getChallengeSubmitLabel()))
            ->action('authenticate');
    }

    protected function isSudoRateLimited(Authenticatable $user): bool
    {
        $rateLimitKey = 'pf-sudo-challenge:' . hash('sha256', 'sudo|' . $user->getAuthIdentifier() . '|' . request()->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 5)) {
            $this->getRateLimitedNotification(
                new TooManyRequestsException(
                    static::class,
                    'authenticate',
                    request()->ip(),
                    RateLimiter::availableIn($rateLimitKey),
                ),
            )?->send();

            return true;
        }

        RateLimiter::hit($rateLimitKey);

        return false;
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('profile-filament::auth/sudo/sudo.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('profile-filament::auth/sudo/sudo.notifications.throttled') ?: []) ? __('profile-filament::auth/sudo/sudo.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }
}
