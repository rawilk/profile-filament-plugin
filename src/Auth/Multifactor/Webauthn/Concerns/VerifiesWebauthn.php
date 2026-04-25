<?php

declare(strict_types=1);

namespace Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use LogicException;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\FindSecurityKeyToAuthenticateAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Actions\GenerateSecurityKeyAuthenticationOptionsAction;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Contracts\HasWebauthn;
use Rawilk\ProfileFilament\Auth\Multifactor\Webauthn\Enums\WebauthnSession;
use Rawilk\ProfileFilament\Events\Webauthn\WebauthnKeyUsed;
use Rawilk\ProfileFilament\Support\Config;

trait VerifiesWebauthn
{
    public function isEnabled(Authenticatable $user): bool
    {
        if (! ($user instanceof HasWebauthn)) {
            throw new LogicException('The user model must implement the [' . HasWebauthn::class . '] interface to use webauthn authentication');
        }

        /** @var \Illuminate\Database\Eloquent\Model $user */
        if ($user->relationLoaded('securityKeys')) {
            return $user->securityKeys->isNotEmpty();
        }

        return $user->securityKeys()->exists();
    }

    public function generateAuthenticationOptions(?Authenticatable $user = null): string
    {
        $generateOptionsAction = Config::getWebauthnAction(
            'generate_security_key_authentication_options',
            GenerateSecurityKeyAuthenticationOptionsAction::class,
        );

        return $generateOptionsAction(user: $user);
    }

    public function isValidSecurityKeyChallenge(
        string $response,
        Request $request,
        ?Authenticatable $user = null,
        bool $requiresPasskey = false,
    ): bool {
        $findSecurityKeyAction = Config::getWebauthnAction('find_security_key_to_authenticate', FindSecurityKeyToAuthenticateAction::class);

        $securityKey = $findSecurityKeyAction(
            publicKeyCredentialJson: $response,
            securityKeyOptionsJson: WebauthnSession::AuthenticationOptions->pull(),
            requiresPasskey: $requiresPasskey,
            userBeingAuthenticated: $user,
        );

        if (! $securityKey) {
            return false;
        }

        $request->merge([
            'webauthnResponse' => $response,
        ]);

        WebauthnKeyUsed::dispatch($user ?? $securityKey->user, $securityKey, $request);

        return true;
    }
}
