<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Plugin\Concerns;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rawilk\ProfileFilament\Auth\Login\AuthenticationPipes\PrepareAuthenticatedSession;
use Rawilk\ProfileFilament\Auth\Multifactor\Contracts\MultiFactorAuthenticationProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\ChallengePipes\AuthenticateUser;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\ChallengePipes\GuardAgainstExpiredPasswordConfirmation;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\MultiFactorChallenge;
use Rawilk\ProfileFilament\Auth\Multifactor\Filament\SetUpRequiredMultiFactorAuthentication;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\Contracts\RecoveryProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Recovery\RecoveryCodeProvider;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Exceptions\InvalidConfig;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\PasskeyLoginPipes;
use Webauthn\AuthenticatorSelectionCriteria;

trait HasMultiFactorAuth
{
    /**
     * @var array<MultiFactorAuthenticationProvider>|MultiFactorAuthenticationProvider|Closure
     */
    protected array|MultiFactorAuthenticationProvider|Closure $multiFactorAuthenticationProviders = [];

    protected bool|Closure $isMultiFactorAuthenticationRequired = false;

    /** @var string|Closure|array<class-string, string>|null */
    protected string|Closure|array|null $setUpRequiredMultiFactorAuthenticationRouteAction = null;

    protected ?RecoveryProvider $recoveryProvider = null;

    /** @var string|Closure|array<class-string, string>|null */
    protected string|Closure|array|null $multiFactorAuthenticationAction = null;

    /** @var array<string, MultiFactorAuthenticationProvider> */
    protected array $multiFactorAuthenticationProviderCache = [];

    protected array|Closure|null $multiFactorChallengePipes = null;

    protected string $multiFactorAuthenticationRouteSlug = 'sessions/two-factor-challenge';

    protected bool $addPasskeyActionToLogin = false;

    protected array|Closure|null $passkeyLoginPipes = null;

    protected ?string $webauthnAuthenticatorAttachment = null;

    /**
     * Note: if you change this value in production, users will need to re-register their security keys.
     */
    protected string $webauthnUserVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;

    protected ?string $webauthnAuthenticatorResidentKey = AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED;

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
        string|Closure|array|null $setUpRequiredAction = SetUpRequiredMultiFactorAuthentication::class,
    ): static {
        $this->multiFactorAuthenticationProviders = $providers;
        $this->requiresMultiFactorAuthentication($isRequired);
        $this->multiFactorAuthenticationAction = $challengeAction;
        $this->setUpRequiredMultiFactorAuthenticationRouteAction = $setUpRequiredAction;
        $this->multiFactorRecovery(
            $recoveryProvider === false
                ? null
                : $recoveryProvider ?? RecoveryCodeProvider::make(),
        );

        return $this;
    }

    public function passkeyLogin(bool $condition = true): static
    {
        $this->addPasskeyActionToLogin = $condition;

        return $this;
    }

    public function shouldAddPasskeyActionToLogin(): bool
    {
        return $this->addPasskeyActionToLogin;
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

    /**
     * @return string|Closure|array<class-string, string>|null
     */
    public function getSetUpRequiredMultiFactorAuthenticationRouteAction(): string|Closure|array|null
    {
        return $this->setUpRequiredMultiFactorAuthenticationRouteAction;
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

    public function sendPasskeyLoginThrough(array|Closure|null $pipes): static
    {
        $this->passkeyLoginPipes = $pipes;

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

    public function getPasskeyLoginPipes(): array
    {
        return $this->evaluate($this->passkeyLoginPipes) ?? [
            PasskeyLoginPipes\FindPasskey::class,
            PasskeyLoginPipes\AuthenticateUser::class,
            PrepareAuthenticatedSession::class,
        ];
    }

    public function setWebauthnAuthenticatorAttachment(?string $attachment): static
    {
        if (filled($attachment)) {
            $attachment = strtolower($attachment);
        }

        $supportedValues = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENTS;

        throw_unless(
            in_array($attachment, $supportedValues, true),
            InvalidConfig::invalidAuthenticatorAttachment($attachment, $supportedValues),
        );

        $this->webauthnAuthenticatorAttachment = $attachment;

        return $this;
    }

    /**
     * Note: Setting this has no effect on passkey login, or when
     * a resident key is required (which is set to 'required' by default)
     */
    public function setWebauthnUserVerification(string $verification): static
    {
        $verification = strtolower($verification);

        $supportedValues = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENTS;

        throw_unless(
            in_array($verification, $supportedValues, true),
            InvalidConfig::invalidUserVerification($verification, $supportedValues),
        );

        $this->webauthnUserVerification = $verification;

        return $this;
    }

    public function setWebauthnResidentKeyRequirement(?string $requirement): static
    {
        if (filled($requirement)) {
            $requirement = strtolower($requirement);
        }

        $supportedValues = AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENTS;

        throw_unless(
            in_array($requirement, $supportedValues, true),
            InvalidConfig::invalidResidentKeyRequirement($requirement, $supportedValues),
        );

        $this->webauthnAuthenticatorResidentKey = $requirement;

        return $this;
    }

    public function getWebauthnAuthenticatorAttachment(): ?string
    {
        return $this->webauthnAuthenticatorAttachment;
    }

    public function getWebauthnUserVerification(): string
    {
        return $this->webauthnUserVerification;
    }

    public function getWebauthnResidentKeyRequirement(): ?string
    {
        return $this->webauthnAuthenticatorResidentKey;
    }

    public function getSetUpRequiredMultiFactorAuthenticationUrl(array $parameters = []): ?string
    {
        if (! $this->hasMultiFactorAuthentication()) {
            return null;
        }

        return route(Filament::getCurrentOrDefaultPanel()->getSetUpRequiredMultiFactorAuthenticationRouteName(), $parameters);
    }
}
