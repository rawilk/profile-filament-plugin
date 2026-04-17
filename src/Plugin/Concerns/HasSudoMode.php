<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Auth\Sudo\Contracts\SudoChallengeProvider;
use Rawilk\ProfileFilament\Auth\Sudo\Filament\SudoChallenge;
use Rawilk\ProfileFilament\Auth\Sudo\Password\SudoPasswordProvider;

trait HasSudoMode
{
    /** @var array<SudoChallengeProvider>|SudoChallengeProvider|Closure|null */
    protected array|SudoChallengeProvider|Closure|null $sudoChallengeProviders = null;

    /** @var array<string, SudoChallengeProvider> */
    protected array $sudoChallengeProviderCache = [];

    protected string $sudoChallengeRouteSlug = 'sessions/sudo-challenge';

    /** @var string|Closure|array<class-string, string>|null */
    protected string|Closure|array|null $sudoChallengeRouteAction = SudoChallenge::class;

    public function sudoMode(
        array|SudoChallengeProvider|Closure|null|bool $providers,
        string|Closure|array|null $routeChallengeAction = SudoChallenge::class,
    ): static {
        $this->sudoChallengeProviders = $providers === false
            ? []
            : $providers;

        $this->sudoChallengeRouteAction = $routeChallengeAction;

        return $this;
    }

    public function sudoChallengeRouteSlug(string $slug): static
    {
        $this->sudoChallengeRouteSlug = $slug;

        return $this;
    }

    public function getSudoChallengeRouteSlug(): string
    {
        return Str::start($this->sudoChallengeRouteSlug, '/');
    }

    public function getSudoChallengeProvider(string $id): ?SudoChallengeProvider
    {
        return $this->getSudoChallengeProviders()[$id] ?? null;
    }

    /**
     * @return string|Closure|array<class-string, string>|null
     */
    public function getSudoChallengeRouteAction(): string|Closure|array|null
    {
        return $this->sudoChallengeRouteAction;
    }

    public function getSudoChallengeProviders(): array
    {
        if (! empty($this->sudoChallengeProviderCache)) {
            return $this->sudoChallengeProviderCache;
        }

        // Default sudo mode to password verification if none have been provided to the panel.
        if ($this->sudoChallengeProviders === null) {
            $passwordProvider = SudoPasswordProvider::make();

            return $this->sudoChallengeProviderCache = [
                $passwordProvider->getId() => $passwordProvider,
            ];
        }

        $providers = $this->evaluate($this->sudoChallengeProviders);

        if (empty($providers)) {
            return $this->sudoChallengeProviderCache = [];
        }

        return $this->sudoChallengeProviderCache = Collection::wrap($providers)
            ->mapWithKeys(fn (SudoChallengeProvider $provider): array => [$provider->getId() => $provider])
            ->all();
    }

    public function hasSudoMode(): bool
    {
        return ! empty($this->getSudoChallengeProviders());
    }
}
