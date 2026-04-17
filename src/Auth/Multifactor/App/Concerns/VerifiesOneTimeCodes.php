<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\App\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use PragmaRX\Google2FAQRCode\Google2FA;
use Rawilk\ProfileFilament\Auth\Multifactor\App\Contracts\HasAppAuthentication;
use Rawilk\ProfileFilament\Events\AuthenticatorApps\TwoFactorAppUsed;
use SensitiveParameter;

/**
 * @property Google2FA $google2FA
 */
trait VerifiesOneTimeCodes
{
    /**
     * 8 keys (respectively 4 minutes) past and future
     */
    protected int $codeWindow = 8;

    public function isEnabled(User $user): bool
    {
        if (! ($user instanceof HasAppAuthentication)) {
            throw new LogicException('The user model must implement the [' . HasAppAuthentication::class . '] interface to use app authentication.');
        }

        /** @var Model $user */
        if ($user->relationLoaded('authenticatorApps')) {
            return $user->authenticatorApps->isNotEmpty();
        }

        return $user->authenticatorApps()->exists();
    }

    public function verifyCode(
        #[SensitiveParameter] string $code,
        #[SensitiveParameter] ?string $secret = null,
        bool $shouldPreventCodeReuse = false,
    ): bool {
        if (! $shouldPreventCodeReuse) {
            return $this->google2FA->verifyKey($secret ?? '', $code, $this->getCodeWindow());
        }

        $cacheKey = 'pf.app_authentication_codes.' . hash('sha256', $secret . $code);

        $timestamp = $this->google2FA->verifyKeyNewer($secret ?? '', $code, cache()->get($cacheKey), $this->getCodeWindow());

        if ($timestamp !== false) {
            if ($timestamp === true) {
                $timestamp = $this->google2FA->getTimestamp();
            }

            cache()->put($cacheKey, $timestamp, ($this->getCodeWindow() + 1) * 60);

            return true;
        }

        return false;
    }

    public function isValidCodeForAnApp(#[SensitiveParameter] $code, HasAppAuthentication&Authenticatable $user): bool
    {
        foreach ($user->authenticatorApps as $authenticatorApp) {
            if ($this->verifyCode($code, $authenticatorApp->secret, shouldPreventCodeReuse: true)) {
                $authenticatorApp->touch('last_used_at');

                TwoFactorAppUsed::dispatch($user, $authenticatorApp);

                return true;
            }
        }

        return false;
    }

    public function codeWindow(int $window): static
    {
        $this->codeWindow = $window;

        return $this;
    }

    public function getCodeWindow(): int
    {
        return $this->codeWindow;
    }
}
