<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament;

use InvalidArgumentException;

final class Features
{
    /**
     * The callback that will generate the "default" version of the features.
     *
     * @var callable|Features|null
     */
    public static $defaultCallback;

    private bool $twoFactorAuthentication = true;

    private array $twoFactorOptions = [
        'authenticatorApps' => true,
        'webauthn' => true,
    ];

    /**
     * Allow passwordless login with passkeys.
     *
     * Note: two-factor authentication must be enabled as well.
     */
    private bool $passkeys = true;

    /**
     * Require re-authentication for sensitive actions,
     * such as enabling/disabling mfa.
     */
    private bool $sudoMode = true;

    private array $updatePasswordConfig = [
        'current_password' => true,
        'password_confirmation' => true,
    ];

    private bool $showPasswordResetLink = true;

    public static function make(): self
    {
        return new self;
    }

    /**
     * Set the default callback to be used for determining the default features.
     *
     *  If no arguments are passed, the default features instance will be returned.
     *
     * @return void|self
     */
    public static function defaults(callable|Features $callback = null)
    {
        if (is_null($callback)) {
            return self::default();
        }

        if (! is_callable($callback) && ! $callback instanceof self) {
            throw new InvalidArgumentException('The given callback should be callable.');
        }

        self::$defaultCallback = $callback;
    }

    public static function default(): self
    {
        $features = is_callable(self::$defaultCallback)
            ? call_user_func(self::$defaultCallback)
            : self::$defaultCallback;

        return $features instanceof self ? $features : new self;
    }

    public function requireCurrentPasswordToUpdatePassword(bool $condition = true): self
    {
        $this->updatePasswordConfig['current_password'] = $condition;

        return $this;
    }

    public function requirePasswordConfirmationToUpdatePassword(bool $condition = true): self
    {
        $this->updatePasswordConfig['password_confirmation'] = $condition;

        return $this;
    }

    public function twoFactorAuthentication(
        bool $enabled = null,
        bool $authenticatorApps = null,
        bool $webauthn = null,
        bool $passkeys = null,
    ): self {
        if (is_bool($enabled)) {
            $this->twoFactorAuthentication = $enabled;
        }

        if (is_bool($authenticatorApps)) {
            $this->twoFactorOptions['authenticatorApps'] = $authenticatorApps;
        }

        if (is_bool($webauthn)) {
            $this->twoFactorOptions['webauthn'] = $webauthn;
        }

        if (is_bool($passkeys)) {
            $this->passkeys = $passkeys;
        }

        return $this;
    }

    public function hidePasswordResetLink(): self
    {
        $this->showPasswordResetLink = false;

        return $this;
    }

    public function usePasskeys(bool $condition = true): self
    {
        $this->passkeys = $condition;

        return $this;
    }

    public function useSudoMode(bool $condition = true): self
    {
        $this->sudoMode = $condition;

        return $this;
    }

    public function hasPasskeys(): bool
    {
        return $this->passkeys && $this->hasTwoFactorAuthentication();
    }

    public function hasWebauthn(): bool
    {
        return $this->hasTwoFactorOption('webauthn');
    }

    public function hasAuthenticatorApps(): bool
    {
        return $this->hasTwoFactorOption('authenticatorApps');
    }

    public function hasTwoFactorOption(string $option): bool
    {
        if (! $this->twoFactorAuthentication) {
            return false;
        }

        return $this->twoFactorOptions[$option];
    }

    public function hasSudoMode(): bool
    {
        return $this->sudoMode;
    }

    public function hasTwoFactorAuthentication(): bool
    {
        return $this->twoFactorAuthentication;
    }

    /**
     * Is the user's current password required to update their password?
     */
    public function requiresCurrentPassword(): bool
    {
        return $this->updatePasswordConfig['current_password'];
    }

    /**
     * Is a password confirmation required to update the user's password?
     */
    public function requiresPasswordConfirmation(): bool
    {
        return $this->updatePasswordConfig['password_confirmation'];
    }

    public function shouldShowPasswordResetLink(): bool
    {
        return $this->showPasswordResetLink;
    }
}
