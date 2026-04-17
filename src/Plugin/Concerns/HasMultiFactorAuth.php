<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\PrepareAuthenticatedSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\ChallengePipes\AuthenticateUser;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\ChallengePipes\GuardAgainstExpiredPasswordConfirmation;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\MultiFactorChallenge;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\RecoveryProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\RecoveryCodeProvider;

trait HasMultiFactorAuth
{
    /**
     * @var array<MultiFactorAuthenticationProvider>|MultiFactorAuthenticationProvider|Closure
     */
    protected array|MultiFactorAuthenticationProvider|Closure $multiFactorAuthenticationProviders = [];

    protected bool|Closure $isMultiFactorAuthenticationRequired = false;

    protected ?RecoveryProvider $recoveryProvider = null;

    /** @var string|Closure|array<class-string, string>|null */
    protected string|Closure|array|null $multiFactorAuthenticationAction = null;

    /** @var array<string, MultiFactorAuthenticationProvider> */
    protected array $multiFactorAuthenticationProviderCache = [];

    protected array|Closure|null $multiFactorChallengePipes = null;

    protected string $multiFactorAuthenticationRouteSlug = 'sessions/two-factor-challenge';

    public function hasMultiFactorAuthentication(): bool
    {
        return ! empty($this->getMultiFactorAuthenticationProviders());
    }

    public function isMultiFactorAuthenticationRequired(): bool
    {
        return (bool) $this->evaluate($this->isMultiFactorAuthenticationRequired);
    }

    public function multiFactorAuthentication(
        array|MultiFactorAuthenticationProvider|Closure $providers,
        bool|Closure $isRequired = false,
        null|bool|RecoveryProvider $recoveryProvider = null,
        string|Closure|array|null $challengeAction = MultiFactorChallenge::class,
    ): static {
        $this->multiFactorAuthenticationProviders = $providers;
        $this->requiresMultiFactorAuthentication($isRequired);
        $this->multiFactorAuthenticationAction = $challengeAction;
        $this->multiFactorRecovery(
            $recoveryProvider === false
                ? null
                : $recoveryProvider ?? RecoveryCodeProvider::make(),
        );

        return $this;
    }

    public function multiFactorRecovery(?RecoveryProvider $provider): static
    {
        $this->recoveryProvider = $provider;

        return $this;
    }

    public function requiresMultiFactorAuthentication(bool|Closure $condition = true): static
    {
        $this->isMultiFactorAuthenticationRequired = $condition;

        return $this;
    }

    public function getMultiFactorRecoveryProvider(): ?RecoveryProvider
    {
        return $this->recoveryProvider;
    }

    public function isMultiFactorRecoverable(): bool
    {
        return filled($this->getMultiFactorRecoveryProvider());
    }

    /**
     * @return array<string, MultiFactorAuthenticationProvider>
     */
    public function getMultiFactorAuthenticationProviders(): array
    {
        if (! empty($this->multiFactorAuthenticationProviderCache)) {
            return $this->multiFactorAuthenticationProviderCache;
        }

        $providers = $this->evaluate($this->multiFactorAuthenticationProviders);

        if (empty($providers)) {
            return [];
        }

        return $this->multiFactorAuthenticationProviderCache = Collection::wrap($providers)
            ->mapWithKeys(fn (MultiFactorAuthenticationProvider $provider): array => [$provider->getId() => $provider])
            ->all();
    }

    /**
     * @return string|Closure|array<class-string, string>|null
     */
    public function getMultiFactorAuthenticationRouteAction(): string|Closure|array|null
    {
        return $this->multiFactorAuthenticationAction;
    }

    public function getMultiFactorAuthenticationProvider(string $id): ?MultiFactorAuthenticationProvider
    {
        return $this->getMultiFactorAuthenticationProviders()[$id] ?? null;
    }

    public function multiFactorAuthenticationRouteSlug(string $slug): static
    {
        $this->multiFactorAuthenticationRouteSlug = $slug;

        return $this;
    }

    public function getMultiFactorAuthenticationRouteSlug(): string
    {
        return Str::start($this->multiFactorAuthenticationRouteSlug, '/');
    }

    public function sendMultiFactorChallengeThrough(array|Closure|null $pipes): static
    {
        $this->multiFactorChallengePipes = $pipes;

        return $this;
    }

    public function getMultiFactorChallengePipes(?Authenticatable $user = null): array
    {
        return $this->evaluate($this->multiFactorChallengePipes, ['user' => $user]) ?? [
            GuardAgainstExpiredPasswordConfirmation::class,
            AuthenticateUser::class,
            PrepareAuthenticatedSession::class,
        ];
    }
}
